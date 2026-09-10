import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-deviation-dec',
  templateUrl: './deviation-dec.component.html',
  styleUrls: ['./deviation-dec.component.css'],
})
export class DeviationDecComponent implements OnInit {
  emp: any;
  isView = false;
  selectedDev = [];
  departments: Object;

  constructor(private service: DataAccessService, private router: Router) {}
  ngOnInit() {
    this.getDeviation();
    this.Employee_qa();
  }
  result;
  Employee_qa() {
    this.service
      .get('employee.php?type=getDeptalternateperson1')
      .subscribe((response) => {
        this.result = response;
      });
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
        'deviation.php?type=getDeviationDessionByRaisedDept&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.results = response;
        console.log(this.results);
      });
  }
  delData(index) {
    this.actionplans.splice(index, 1);
  }
  actionplans = [];

  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    console.log('emp', this.emp);

    let temp = data.value;
    if (this.emp) {
      let obj = this.result.find((item) => item.id == this.emp);
      if (obj && obj.id) {
        temp.emp = obj.firstname + ' ' + obj.lastname;
      }
    } else {
      console.error('not getting the Employee');
    }
    console.log('temp :>> ', temp);
    this.actionplans.push(temp);
    console.log(this.actionplans);
    data.reset();
  }
  viewDeviation(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
  }
  returnDeviation() {
    this.router.navigate(['/hrfordepthead']);
  }
  uploadDeviation(url) {
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
    // window.open(this.selectedResult['documents']);
  }
  Save(data) {
    let temp = data.value;
    this.service
      .post(
        'deviation.php?type=DeviationDec&id=' + this.selectedDev['id'],
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          this.getDeviation();
          this.isView = false;
          alert('Deviation Successfully Proceed... ');
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
  }
}
