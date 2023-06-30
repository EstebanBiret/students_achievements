
$(document).on('click', '.student-details', function(event) {

    // remettre l'option sélectionnée du premier formulaire (cohortes)
    $('#id_cohort').prop('selectedIndex', 0);

    //cacher l'ancien formulaire
    $('#hidden-form').hide();

    $('.export-cohort-blocks').hide(); //on cache le formulaire d'export par cohortes avec blocks
    $('.export-cohort-no-blocks').hide(); //on cache le formulaire d'export par cohortes sans blocks
    
    event.preventDefault(); // Empêche le comportement par défaut du lien
    
    // Récupérer l'ID de l'étudiant à partir de l'attribut data du lien
    var studentId = $(this).attr('studentId');

    // Récupérer lid de la cohorte
    var cohortId = $(this).attr('cohortId');

    var value = cohortId + '-' + studentId;

    var completion = document.getElementById('completion-student');
    completion.innerHTML = '';

    
    //get the first and lastname of the selected student
    $.ajax({
      url: 'ajax/ajax_details_of_the_student.php',
      type: 'POST',
      data: { studentId: studentId },
      success: function(response) {
        
        var table = document.getElementById('result-table');
        table.innerHTML = '';

        var numberStudent = document.getElementById('number-student');
        numberStudent.innerHTML = ''; //clear number of result div

        //student's info
        var studentFirstname = response[0][0];
        var studentLastname = response[0][1];

        var container = document.getElementById('container');
        container.innerHTML =  studentFirstname + ' ' + studentLastname;

        $.ajax({
          url:'ajax/ajax_test_cohort_blocks.php',
          type: 'POST',
          data: { cohortId: cohortId },
          success: function(response) {
  
            if(response[0] == 0){ //pas de blocks
  
                        $.ajax({
                          url:'ajax/ajax_student_cohort_no_blocks.php',
                          type: 'POST',
                          data: { value: value },
                          success: function(response) {
                      
                            var container = document.getElementById('completion-student');
              
                            // Boucle pour parcourir chaque séquence
                            for (var i = 0; i < response.length; i++) {
                              var sequence = response[i];
  
                              // Créer un élément de titre pour le cours avec la classe "course-title"
                              var courseTitle = document.createElement('summary');
                              courseTitle.classList.add('course-title');
                              courseTitle.textContent = sequence.sequenceName;
  
                              // Créer une section pour le cours avec la classe "course-section"
                              var courseSection = document.createElement('details');
                              courseSection.classList.add('course-section');
  
                              // Ajouter le titre du cours à la section
                              courseSection.appendChild(courseTitle);
  
                              if (Array.isArray(sequence.activities) && sequence.activities.length > 0) {
                                  // Créer un tableau pour chaque cours avec la classe "activity-table"
                                  var table = document.createElement('table');
                                  table.classList.add('activity-table');
                                  var tableHeader = document.createElement('thead');
                                  var tableBody = document.createElement('tbody');
  
                                  // Créer la ligne d'en-tête du tableau
                                  var headerRow = document.createElement('tr');
                                  var nameHeader = document.createElement('th');
                                  nameHeader.textContent = 'Nom de l\'activité';
                                  var typeHeader = document.createElement('th');
                                  typeHeader.textContent = 'Type';
                                  var completionHeader = document.createElement('th');
                                  completionHeader.textContent = 'Statut de complétion';
                                  var dateHeader= document.createElement('th');
                                  dateHeader.textContent = 'Date d\'ouverture';
  
                                  headerRow.appendChild(nameHeader);
                                  headerRow.appendChild(typeHeader);
                                  headerRow.appendChild(completionHeader);
                                  headerRow.appendChild(dateHeader);
                                  tableHeader.appendChild(headerRow);
  
                                  // Boucle pour parcourir les activités du cours
                                  for (var l = 0; l < sequence.activities.length; l++) {
                                      var activity = sequence.activities[l];
  
                                      // Créer une ligne pour chaque activité
                                      var activityRow = document.createElement('tr');
                                      var nameCell = document.createElement('td');
                                      var nameLink = document.createElement('a');
                                      nameLink.href = activity.link;
                                      nameLink.target = '_blank';
                                      nameLink.textContent = activity.name;
                                      nameCell.appendChild(nameLink);

                                      var typeCell = document.createElement('td');
                                      typeCell.textContent = activity.type;
                                      var dateCell = document.createElement('td');
                                      dateCell.textContent = activity.date;
                                      var completionCell = document.createElement('td');
                                      var completionImage = document.createElement('img');
                                      completionImage.classList.add('img-completion');
  
                                      if (activity.completion === 'Complété') {
                                        completionImage.src = 'img/complete.png';
                                        completionImage.alt = 'Complété';
                                      } else if (activity.completion === 'Pas complété') {
                                        completionImage.src = 'img/uncomplete.png';
                                        completionImage.alt = 'Pas complété';
                                      }
                                      
                                      completionCell.appendChild(completionImage);
  
                                      activityRow.appendChild(nameCell);
                                      activityRow.appendChild(typeCell);
                                      activityRow.appendChild(completionCell);
                                      activityRow.appendChild(dateCell);
                                      tableBody.appendChild(activityRow);
                                  }
  
                                  // Ajouter l'en-tête et le corps du tableau au tableau
                                  table.appendChild(tableHeader);
                                  table.appendChild(tableBody);
  
                                  // Ajouter le tableau à la section du cours
                                  courseSection.appendChild(table);
                              }
  
                              // Vérifier si le cours a des activités
                              if (Array.isArray(sequence.activities) && sequence.activities.length > 0) {
                                //do nothing, my name is EstebanTheGOAT
                              }
                              else {
                                courseSection.classList.add('empty'); // Ajouter une classe "empty" pour les cours vides
                              }
  
                              // Ajouter la section du cours à la liste des cours
                              container.appendChild(courseSection);
                            }

                            $('.export-student-cohort-no-blocks').show(); //on affiche le formulaire d'export cohortes mais pas blocks
                
                          },
  
                          error: function(xhr, status, error) {
                            // Gestion des erreurs de la requête cohorte no blocks
                            console.log(xhr);
                            console.log(status);
                            console.log(error);
                          }
                        });
  
                      }
  
                      else { //présence de block.s
  
                        $.ajax({ // requête pour chercher les infos de l'étudiant, en fonction de sa promo et de son id
                          url: 'ajax/ajax_student_cohort_blocks.php',
                          type: 'POST',
                          data: { value: value }, // Envoyer l'ID de l'étudiant sélectionné et sa cohorte en tant que paramètre
                          success: function(response) {
                                          
                            var container = document.getElementById('completion-student');
                
                            // Boucle pour parcourir chaque block
                            for (var i = 0; i < response.length; i++) {
                              var block = response[i];
                
                              // Créer un élément de titre pour le block avec la classe "block-title"
                              var blockTitle = document.createElement('summary');
                              blockTitle.classList.add('block-title');
                              blockTitle.textContent = block.blockName;
                  
                              // Créer une section pour le block avec la classe "block-section"
                              var blockSection = document.createElement('details');
                              blockSection.classList.add('block-section');
                  
                              // Ajouter le titre du block à la section
                              blockSection.appendChild(blockTitle);
                  
                              // Créer une liste non ordonnée pour les modules des blocks avec la classe "module-list"
                              var moduleList = document.createElement('ul');
                              moduleList.classList.add('module-list');
                  
                              // Vérifier si block.modules est défini et est un tableau
                              if (Array.isArray(block.modules)) {
                                  // Boucle pour parcourir les modules du block
                                  for (var j = 0; j < block.modules.length; j++) {
                                      var module = block.modules[j];
                  
                                      // Créer un élément de titre pour le module avec la classe "module-title"
                                      var moduleTitle = document.createElement('summary');
                                      moduleTitle.classList.add('module-title');
                                      moduleTitle.textContent = module.moduleName;
                  
                                      // Créer une section pour le module avec la classe "module-section"
                                      var moduleSection = document.createElement('details');
                                      moduleSection.classList.add('module-section');
                  
                                      // Ajouter le titre du module à la section
                                      moduleSection.appendChild(moduleTitle);
                  
                                      // Créer une liste non ordonnée pour les cours du module avec la classe "course-list"
                                      var courseList = document.createElement('ul');
                                      courseList.classList.add('course-list');
                  
                                      // Vérifier si module.courses est défini et est un tableau
                                      if (Array.isArray(module.courses)) {
                                          // Boucle pour parcourir les cours du module
                                          for (var k = 0; k < module.courses.length; k++) {
                                              var course = module.courses[k];
                  
                                              // Créer un élément de titre pour le cours avec la classe "course-title"
                                              var courseTitle = document.createElement('summary');
                                              courseTitle.classList.add('course-title');
                                              courseTitle.textContent = course.courseName;
                  
                                              // Créer une section pour le cours avec la classe "course-section"
                                              var courseSection = document.createElement('details');
                                              courseSection.classList.add('course-section');
                  
                                              // Ajouter le titre du cours à la section
                                              courseSection.appendChild(courseTitle);
                  
                                              if (Array.isArray(course.activities) && course.activities.length > 0) {
                                                  // Créer un tableau pour chaque cours avec la classe "activity-table"
                                                  var table = document.createElement('table');
                                                  table.classList.add('activity-table');
                                                  var tableHeader = document.createElement('thead');
                                                  var tableBody = document.createElement('tbody');
                  
                                                  // Créer la ligne d'en-tête du tableau
                                                  var headerRow = document.createElement('tr');
                                                  var nameHeader = document.createElement('th');
                                                  nameHeader.textContent = 'Nom de l\'activité';
                                                  var typeHeader = document.createElement('th');
                                                  typeHeader.textContent = 'Type';
                                                  var completionHeader = document.createElement('th');
                                                  completionHeader.textContent = 'Statut de complétion';
                                                  var dateHeader= document.createElement('th');
                                                  dateHeader.textContent = 'Date d\'ouverture';
                  
                                                  
                  
                                                  headerRow.appendChild(nameHeader);
                                                  headerRow.appendChild(typeHeader);
                                                  headerRow.appendChild(completionHeader);
                                                  headerRow.appendChild(dateHeader);
                                                  tableHeader.appendChild(headerRow);
                  
                                                  // Boucle pour parcourir les activités du cours
                                                  for (var l = 0; l < course.activities.length; l++) {
                                                      var activity = course.activities[l];
                  
                                                      // Créer une ligne pour chaque activité
                                                      var activityRow = document.createElement('tr');
                                                      var nameCell = document.createElement('td');
                                                      var nameLink = document.createElement('a');
                                                      nameLink.href = activity.link;
                                                      nameLink.target = '_blank';
                                                      nameLink.textContent = activity.name;
                                                      nameCell.appendChild(nameLink);

                                                      var typeCell = document.createElement('td');
                                                      typeCell.textContent = activity.type;
                                                      var dateCell = document.createElement('td');
                                                      dateCell.textContent = activity.date;
                                                      var completionCell = document.createElement('td');
                                                      var completionImage = document.createElement('img');
                                                      completionImage.classList.add('img-completion');
                  
                                                      if (activity.completion === 'Complété') {
                                                        completionImage.src = 'img/complete.png';
                                                        completionImage.alt = 'Complété';
                                                      } else if (activity.completion === 'Pas complété') {
                                                        completionImage.src = 'img/uncomplete.png';
                                                        completionImage.alt = 'Pas complété';
                                                      }
                                                      
                                                      completionCell.appendChild(completionImage);
                  
                                                      activityRow.appendChild(nameCell);
                                                      activityRow.appendChild(typeCell);
                                                      activityRow.appendChild(completionCell);
                                                      activityRow.appendChild(dateCell);
                                                      tableBody.appendChild(activityRow);
                                                  }
                  
                                                  // Ajouter l'en-tête et le corps du tableau au tableau
                                                  table.appendChild(tableHeader);
                                                  table.appendChild(tableBody);
                  
                                                  // Ajouter le tableau à la section du cours
                                                  courseSection.appendChild(table);
                                              }
                  
                                              // Vérifier si le cours a des activités
                                              if (Array.isArray(course.activities) && course.activities.length > 0) {
                                                //do nothing, my name is EstebanTheGOAT
                                              }
                                              else {
                                                courseSection.classList.add('empty'); // Ajouter une classe "empty" pour les cours vides
                                              }
                  
                                              // Ajouter la section du cours à la liste des cours
                                              courseList.appendChild(courseSection);
                                          }
                                      } else {
                                        moduleSection.classList.add('empty'); // Ajouter une classe "empty" pour les modules vides
                                      }
                  
                                      // Ajouter la liste des cours à la section du module
                                      moduleSection.appendChild(courseList);
                  
                                      // Ajouter la section du module à la liste des modules
                                      moduleList.appendChild(moduleSection);
                                  }
                              } else {
                                blockSection.classList.add('empty'); // Ajouter une classe "empty" pour les blocks vides
                              }
                  
                              // Ajouter la liste des modules à la section du block
                              blockSection.appendChild(moduleList);
                  
                              // Ajouter la section du block au conteneur HTML
                              container.appendChild(blockSection);
                
                            } 

                            $('.export-student-cohort-blocks').show(); //on affiche le formulaire d'export cohortes et blocks

                          },
                      
                          error: function(xhr, status, error) {
                            // Gestion des erreurs de la requete cohorte blocks
                            console.log(xhr);
                            console.log(status);
                            console.log(error);
                          }
                        });

                      }
  
          },
  
          error: function(xhr, status, error) {
            // Gestion des erreurs de la requête test si cohorte a des blocks ou non 
            console.log(xhr);
            console.log(status);
            console.log(error);
          }
  
        });
      },

      error: function(xhr, status, error) {
        // Gérer les erreurs de la première requete, click sur le lien
        console.log(xhr);
        console.log(status);
        console.log(error);
      }
    });
  });
  
