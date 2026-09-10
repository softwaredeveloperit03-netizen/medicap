import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-subtest',
  templateUrl: './subtest.component.html',
  styleUrls: ['./subtest.component.css']
})
export class SubtestComponent implements OnInit {
  tests;
  subtests;
  isNewTest;
  classification = 'Raw Material';
  dosage_form = 'Powder';
  test = '';
  isRawMaterial = true;
  isPackingMaterial = false;
  isFinishProduct = false;
  isInprocess = false;

  entries = [];
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getSubtests();
    this.getTests();
  }

  checkClassification(value) {
    this.getTests();
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
      this.isFinishProduct = true;
      this.isInprocess = false;
    } else if (value === 'Inprocess') {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
      this.isFinishProduct = false;
      this.isInprocess = true;
    }
  }

  getTests() {
    this.service.get('qaDepartment.php?type=getTests1&classification=' + this.classification + '&dosage_form=' + this.dosage_form)
    .subscribe(response => {
      this.tests = response;
    });
  }

  getSubtests() {
    this.service.get('qaDepartment.php?type=getSubtests')
    .subscribe(response => {
      this.subtests = response;
    });
  }
  
  addTest(testData) {
    this.isNewTest = false;
    this.service.post('qaDepartment.php?type=addSubtest', JSON.stringify(testData))
    .subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Test successfully send for Approval');
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
      this.getSubtests();
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
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
      if (this.classification === this.tests[i].classification && this.dosage_form === this.tests[i].dosage_form && this.test === this.tests[i].subtest && val.includes(value)) {
        this.entries[index] = this.tests[i];
      }
    }
  }

}
