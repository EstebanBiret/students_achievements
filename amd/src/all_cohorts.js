
$(document).ready(function() {
    $('#id_cohort').on('change', function() { //id of all_cohorts_form.php's form

      //hide last form
      $('#hidden-form').hide();

      //hide all export forms
      $('.export-student-no-cohort').hide();
      $('.export-student-cohort-blocks').hide();
      $('.export-student-cohort-no-blocks').hide();
      $('.export-cohort-blocks').hide();
      $('.export-cohort-no-blocks').hide();
      
      //get cohort id
      var cohortId = $(this).val();
  
      //display the html_table that contains all the student in this cohort (firstname, lastname and span for view details)
      $.ajax({
        url: 'ajax/ajax_students_of_this_cohort.php',
        type: 'POST',
        data: { cohortId: cohortId },
        success: function(response) {

          var numberStudent = response[0][0];
          var divResult = document.getElementById('number-student');

          var completion = document.getElementById('completion-student');
          completion.innerHTML = '';

          if (numberStudent != 0) {
            divResult.innerHTML = numberStudent + ' students';
          }
          
          else {
            divResult.innerHTML = 'No result';
          }

          var container = document.getElementById('container');
          container.innerHTML = '';

          var table = document.getElementById('result-table');
          table.innerHTML = '';

              //browse all student's data
              for (var i = 1; i < response.length; i++) {
                  var student = response[i];

                  var info1 = student[0];
                  var info2 = student[1];
                  var info3 = student[2];

                  var newRow = table.insertRow();

                  newRow.insertCell().innerHTML = info1;
                  newRow.insertCell().innerHTML = info2;
                  newRow.insertCell().innerHTML = info3;
              }

          if (numberStudent != 0) { //if there is at least one student, the corresponding export form by cohort is displayed

            $.ajax({
              url:'ajax/ajax_test_cohort_blocks.php',
              type: 'POST',
              data: { cohortId: cohortId },
              success: function(response) {

                if(response[0] == 0){ //cohort not in blocks
                  $('.export-cohort-no-blocks').show();
                }

                else { //cohort in blocks
                  $('.export-cohort-blocks').show();
                }
              },

              error: function(xhr, status, error) {
                //errors of the second ajax request (test if cohort is in blocks or not)
                console.log(xhr);
                console.log(status);
                console.log(error);
              }
            });
        }

        },

          error: function(xhr, status, error) {
            //errors of the first ajax request (get all cohort's students)
            console.log(xhr);
            console.log(status);
            console.log(error);
          }
      });
    });
  });
