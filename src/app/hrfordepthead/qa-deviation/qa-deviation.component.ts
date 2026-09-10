import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-qa-deviation',
  templateUrl: './qa-deviation.component.html',
  styleUrls: ['./qa-deviation.component.css'],
})
export class QaDeviationComponent implements OnInit {
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
      .get('deviation.php?type=getDeviationAprvl_deparetment')
      .subscribe((response) => {
        this.results = response;
        console.log(this.results);
      });
  }

  delData(index) {
    this.actionplans.splice(index, 1);
  }

  // add(data) {
  //   let res = data.value;
  //   this.actionPlans.push(this.res);
  // }

  // add(data) {
  //   if (!data.valid) {
  //     // Optionally handle invalid form state
  //     console.error('Form is invalid');
  //     return;
  //   }
  //   // Extract form data
  //   let res = data.value;
  //   // Add the new row to the actionPlans array
  //   this.actionPlans.push(res);

  // }

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

  quality_head_count = 0;
  it_count = 0;
  engineering_count = 0;
  store_count = 0;
  production_count = 0;
  qc_count = 0;
  qa_count = 0;
  admin_count = 0;
  hr_count = 0;
  warehouse_count = 0;
  bd_count = 0;
  packing_count = 0;
  ra_count = 0;
  // sc_count=0;
  microbiology_count = 0;

  selectedFile2: File;
  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }

  approve(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    const total_count =
      (this.it ? 1 : 0) +
      (this.engineering ? 1 : 0) +
      (this.production ? 1 : 0) +
      (this.qc ? 1 : 0) +
      (this.qa ? 1 : 0) +
      (this.hr ? 1 : 0) +
      (this.warehouse ? 1 : 0) +
      (this.bd ? 1 : 0) +
      (this.packing ? 1 : 0) +
      (this.ra ? 1 : 0) +
      (this.microbiology ? 1 : 0);
    console.log('this.qc :>> ', this.qc);
    console.log('this.microbiology :>> ', this.microbiology);
    console.log('this.packing :>> ', this.packing);
    console.log('this.ra :>> ', this.ra);
    console.log('this.it :>> ', this.it);
    console.log('this.production :>> ', this.production);
    //  console.log('this.sc :>> ', this.sc);
    console.log('this.hr :>> ', this.hr);
    console.log('this.warehouse :>> ', this.warehouse);
    console.log('this.bd :>> ', this.bd);
    console.log('this.engineering :>> ', this.engineering);
    console.log('this.qa :>> ', this.qa);
    //  console.log('this.store :>> ', this.store);
    // console.log('this.quality_head :>> ', this.quality_head);
    // console.log('this.admin :>> ', this.admin);
    console.log('total_count :>> ', total_count);
    let temp = data.value;
    temp['total_count'] = total_count;
    temp['count'] = this.selectedDev['count'];
    temp['actionplans'] = this.actionplans;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];

      uploadData.append(key, value);
    }

    if (this.selectedFile2 !== undefined) {
      uploadData.append('jugad', this.selectedFile2, this.selectedFile2.name);
    }

    this.service
      .post(
        'deviation.php?type=saveDevaitonDeptQAHod&id=' + this.selectedDev['id'], 
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
      
          this.isView = false;
          alert('Deviation Successfully Proceed... ');
          data.resetForm();
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
  }

  // Checkbox state variables
  quality_head = false;
  it = false;
  engineering = false;
  store = false;
  production = false;
  qc = false;
  qa = false;
  admin = false;
  purchase = false;
  warehouse = false;
  bd = false; // Business Development
  packing = false;
  hr = false;
  checkAll1 = false;
  ra = false;
  // sc = false;
  microbiology = false;
  dep_send_count = 0;

  check() {
    if (this.checkAll1 == true) {
      // this.quality_head = true;
      this.it = true;
      this.engineering = true;
      // this.store = true;
      this.production = true;
      this.qc = true;
      this.qa = true;
      // this.admin = true;
      this.hr = true;
      this.warehouse = true;
      this.bd = true;
      this.packing = true;
      this.ra = true;
      // this.sc = true;
      this.microbiology = true;
      this.dep_send_count = 12;
      console.log('this.dep_send_count :>> ', this.dep_send_count);
    } else {
      // this.quality_head = false;
      this.it = false;
      this.engineering = false;
      // this.store = false;
      this.production = false;
      this.qc = false;
      this.qa = false;
      // this.admin = false;
      this.hr = false;
      this.warehouse = false;
      this.bd = false;
      this.packing = false;
      this.ra = false;
      // this.sc = false;
      this.microbiology = false;
      this.dep_send_count = 0;
      console.log('this.dep_send_count :>> ', this.dep_send_count);
    }
  }
  uploadDeviation(url) {
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
    // window.open(this.selectedResult['documents']);
  }
  viewDeviation(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
  }
  returnDeviation() {
    this.router.navigate(['/hrfordepthead']);
  }
  // approve(data) {
  //   let temp = data.value;
  //   temp['actionplans'] = this.actionplans;
  //   this.service
  //     .post(
  //       'deviation.php?type=saveDevaitonDeptQAHod&id=' + this.selectedDev['id'],
  //       JSON.stringify(temp)
  //     )
  //     .subscribe((response) => {
  //       if (response['status'] == 'success') {
  //         this.router.navigate(['/hrfordepthead']);
  //         this.isView = false;
  //         alert('Deviation Successfully Proceed... ');
  //       } else {
  //         alert('Failed: An error occured, please try again!');
  //       }
  //     });
  // }
}
