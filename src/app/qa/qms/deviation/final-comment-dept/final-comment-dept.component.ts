import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-final-comment-dept',
  templateUrl: './final-comment-dept.component.html',
  styleUrls: ['./final-comment-dept.component.css'],
})
export class FinalCommentDeptComponent implements OnInit {
  Department: string;
  constructor(private service: DataAccessService, private router: Router) {
    this.Department = localStorage.getItem('department');
  }
  results;
  isView = false;
  ngOnInit(): void {
    this.getDeviation();
  }
  getDeviation() {
    this.service
      .get(
        'deviation.php?type=getDeviationFinalComment&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.results = response;
        console.log(this.results);
      });
  }
  selectedDev;
  viewDeviation(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
  }
  selectedFile2: File;
  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }
  approve(data) {


     let temp = data.value;
     temp['count'] = this.selectedDev['count'];
     const uploadData = new FormData();
     for (let key in temp) {
       let value = temp[key];

       uploadData.append(key, value);
     }

     if (this.selectedFile2 !== undefined) {
       uploadData.append(
         'jugad',  this.selectedFile2, this.selectedFile2.name  );
     }
      


    this.service.post(
        'deviation.php?type=saveDevaitonReviewFinal111&id=' +
          this.selectedDev['id'] +
          '&deptName=' +
          localStorage.getItem('department'),
        uploadData
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
        
          this.isView = false;
          alert('Deviation Successfully Proceed... ');
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
  }
}
