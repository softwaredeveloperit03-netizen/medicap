import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-deviation-approval',
  templateUrl: './deviation-approval.component.html',
  styleUrls: ['./deviation-approval.component.css'],
})
export class DeviationApprovalComponent implements OnInit {
  isView = false;
  selectedDev = [];
  constructor(private service: DataAccessService, private router: Router) {}
  ngOnInit() {
    this.getDeviation();
  }
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10;
  }

  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }

  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf() {
    this.isView = false;
    //  this.getLogs();
    this.currentPage = 1;
    this.pageSize = 10;
  }
  // ---------------------------------------------------------------------//
  results;
  getDeviation() {
    this.service
      .get(
        'deviation.php?type=getDeviationAprvl&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.results = response;
        console.log(this.results);
      });
  }

  viewDeviation(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
  }
  uploadDeviation(url) {
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
    // window.open(this.selectedResult['documents']);
  }
  returnDeviation() {
    this.router.navigate(['/hrfordepthead']);
  }
  approve(data) {
    let temp = data.value;
    temp['count'] = this.selectedDev['count'];
    console.log('temp :>> ', temp);
    this.service
      .post(
        'deviation.php?type=saveDevaitonDeptHod&id=' +
          this.selectedDev['id'] +
          '&deptName=' +
          localStorage.getItem('department'),
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.router.navigate(['/hrfordepthead']);
          this.isView = false;
          alert('Deviation Successfully Proceed... ');
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
  }
  DeptReturnDeviation(data) {
    let temp = data.value;
    this.service
      .post(
        'deviation.php?type=DeptReturnDeviation&id=' + this.selectedDev['id'],
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
