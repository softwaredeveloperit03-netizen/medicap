import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  subtests;
  classification = 'Raw Material';
  dosage_form = 'Powder';
  tests;
  isNewTest;
  test = '';
  isRawMaterial = true;
  isPackingMaterial = false;
  isFinishProduct = false;
  isInprocess = false;
  status='';
  entries = [];
  test_type='';
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
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
    this.service.get('master/test.php?type=getTests&classification=' + this.classification + '&dosage_form=' + this.dosage_form)
    .subscribe(response => {
      this.tests = response;
    });
  }

  getSubtests() {
    this.service.get('master/test.php?type=getSubTestsLog&classification='+this.classification+'&dosage_form='+this.dosage_form+'&test='+this.test+'&test_type='+this.test_type)
    .subscribe(response => {
      this.subtests = response;
    });
  }

  download() {
    this.service.open('master/test.php?type=downloadSubTestsLog&classification='+this.classification+'&dosage_form='+this.dosage_form+'&test='+this.test+'&test_type='+this.test_type)
  
  }

}
