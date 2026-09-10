import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css'],
})
export class CorrectionComponent implements OnInit {
  selectedFile2: any;
  selectedClient: any;
  results1: Object;
  selectedDev1: any;
  constructor(private service: DataAccessService, private router: Router) {}
  selectedDev;
  isView;
  results;
  ngOnInit(): void {
    this.getPendingReview();
    this.getClients();
    this.getPendingDepthead();
  }
  viewDeviation(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
  }
  clients;
  getClients() {
    this.service.get('common.php?type=getClients').subscribe((response) => {
      this.clients = response;
    });
  }
  getClientName(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedClient = this.clients[index];
    }
  }
  getPendingReview() {
    this.service
      .get('deviation.php?type=get_return_deviation')
      .subscribe((response) => {
        this.results = response;
        // this.checkColumns();
      });
  }
  getPendingDepthead() {
    this.service
      .get('deviation.php?type=get_return_deviationhod')
      .subscribe((response) => {
        this.results1 = response;
        // this.checkColumns();
      });
  }
  viewDeviation1(index) {
    this.selectedDev1 = this.results1[index];
    this.isView = true;
  }
  saveDeviation(data) {
    //   if (!data.valid) {
    //     alert('All fields are required');
    //     return;
    //   }
    //   let temp = data.value;

    //   this.service
    //     .post('deviation.php?type=saveQmsDeviations1&id=' + this.selectedDev['id'], '&deptName=' + localStorage.getItem('department') )
    //     .subscribe((response) => {
    //       if (response['status'] == 'success') {
    //         this.router.navigate(['/qa/qms/deviation']);
    //         alert('Deviation Successfully Proceed...');
    //       } else {
    //         alert('Failed: An error occured, please try again!');
    //       }
    //     });
    // }
    let formData = new FormData();
    const temp = data.value;

    // Append form values to FormData

    for (let key in temp) {
      if (temp.hasOwnProperty(key)) {
        formData.append(key, temp[key]);
      }
    }

    // Append the selected file if available
    if (this.selectedFile2) {
      formData.append('jugad', this.selectedFile2, this.selectedFile2.name);
    }

    // Send data to the server
    this.service
      .post(
        'deviation.php?type=saveQmsDeviations1&id=' + this.selectedDev['id'],
        JSON.stringify(temp)
      )
      .subscribe(
        (response) => {
          if (response['status'] === 'success') {
            this.router.navigate(['/qa/qms/deviation']);
            alert('Deviation Initiated Successfully. Proceed...');
            data.resetForm();
          } else {
            alert('Failed: An error occurred, please try again!');
          }
        }
        // ,
        // (error) => {
        //   // Handle the error from the server response
        //   alert('An unexpected error occurred. Please try again later.');
        //   this.router.navigate(['/qa/qms/deviation']);
        //   console.error('Error occurred:', error);
        // }
      );
  }
  DeptReturnDeviation(data) {
    let temp = data.value;
    this.service
      .post(
        'deviation.php?type=saveDevaitonDeptH&id=' + this.selectedDev['id'],
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
        
          this.isView = false;
          alert('Deviation Successfully Proceed... ');
          data.resetForm();
        } else {
          alert('Deviation Proceed !');
        }
      });
  
  }
}
