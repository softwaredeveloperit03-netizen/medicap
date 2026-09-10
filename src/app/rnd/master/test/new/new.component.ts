import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  tests;
  isNewTest;
  classification = 'Raw Material';
  isRawMaterial = true;
  isPackingMaterial = false;
  isFinishProduct = false;
  isInprocess = false;
  test_type='';
  entries = [];
  dosage_form = '';
  test = '';
  
  constructor(private service: DataAccessService,private router:Router) {
   }
 

  ngOnInit(): void {
  }

  checkClassification(value) {
    if (value === 'Raw Material') {
      this.isRawMaterial = true;
      this.isPackingMaterial = false;
      this.isFinishProduct = false;
      this.isInprocess = false;
    } else if (value === 'Packing Material') {
      this.isRawMaterial = false;
      this.isPackingMaterial = true;
      this.isFinishProduct = false;
      this.isInprocess = false;
    } else if (value === 'Finish Product') {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isFinishProduct = true;
      this.isInprocess = false;
    } else if (value === 'Inprocess') {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isFinishProduct = false;
      this.isInprocess = true;
    }
  }

  
  addTest(testData) {
    this.isNewTest = false;
    let temp=testData;
    temp['test_type']=this.test_type;
    console.log('type',this.test_type);
    this.service.post('master/test.php?type=saveTest', JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] === 'success') {
       alertify.success('Test successfully send for Approval');
        this.router.navigate(['/master/test']);
      } else {
       alertify.error(this.service.t('common.errorOccurred'));
      }
      },
    (error: Response) => {
      if (error.status === 400) {
       alertify.success('An error has occurred.');
      } else {
       alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }

  filterTable(value) {
    this.entries = [];
    let index = 0;
    let len = Object.keys(this.tests).length;
    for (let i = 0; i< len; i++) {
      let val = this.tests[i].test;
      if (this.classification === this.tests[i].classification && this.dosage_form === this.tests[i].dosage_form && val.includes(value)) {
        this.entries[index] = this.tests[i];
      }
    }
  }


}
