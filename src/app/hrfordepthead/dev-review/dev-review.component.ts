import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dev-review',
  templateUrl: './dev-review.component.html',
  styleUrls: ['./dev-review.component.css'],
})
export class DevReviewComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) {}
  results;
  isView = false;
  ngOnInit(): void {
    this.getDeviation();
  }
  getDeviation() {
    this.service
      .get(
        'deviation.php?type=getDeviationImpactQA&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.results = response;
        console.log(this.results);
      });
  }
  uploadDeviation(url) {
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
    // window.open(this.selectedResult['documents']);
  }
  selectedDev;
  viewDeviation(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
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
        'deviation.php?type=saveDevaitonDeptQAReview&id=' +
          this.selectedDev['id'],
        uploadData
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

  // approve(data) {
  //   let temp = data.value;
  //   this.service
  //     .post(
  //       'deviation.php?type=saveDevaitonDeptQAReview&id=' +
  //         this.selectedDev['id'] +
  //         '&deptName=' +
  //         localStorage.getItem('department'),
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
