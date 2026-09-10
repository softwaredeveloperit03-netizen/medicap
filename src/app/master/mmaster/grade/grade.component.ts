import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-grade',
  templateUrl: './grade.component.html',
  styleUrls: ['./grade.component.css']
})
export class GradeComponent implements OnInit {

  constructor(public service: DataAccessService, private router: Router) {
     
   }



   grades;
  ngOnInit(): void {

    this.getGrades();
  }




  getGrades() {
    this.grades = []
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response

    })
  }


  saveGrade(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (data.valid)
      this.service.post('master/master.php?type=saveGrade', JSON.stringify(data.value)).subscribe(response => {
        if (response['status'] == 'success') {
          data.resetForm();
          this.getGrades();
          alertify.success("Saved Successfully");
        } else {
          alertify.error(response['status']);
        }
      }); else {
      alertify.error("All fields are required");
    }
  }

}
