import { Component, OnInit } from '@angular/core';
import { FormGroup, FormArray, FormBuilder, FormControl, ValidatorFn } from '@angular/forms';
import { Router } from '@angular/router';
import { of } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';
declare var swal: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isImpactQuality = false;
  departments;
  market_details = [
    { id: 1, particular: 'Export', check:''},
    { id: 2, particular: 'Domestic', check:''},
  ];
  classifications = [
    { id: 1, particular: 'Documentation ', check: '' },
    { id: 2, particular: 'Vendor ', check: '' },
    { id: 3, particular: 'Infrastructure ', check: '' },
    { id: 4, particular: 'Quality System ', check: '' },
    { id: 5, particular: 'Material  ', check: '' },
    { id: 6, particular: 'Equipments ', check: '' },
    { id: 7, particular: 'Procedure ', check: '' },
    { id: 8, particular: 'New System Implementation ', check: '' },
    { id: 9, particular: 'Method ', check: '' },
    { id: 10, particular: 'Process Formula ', check: '' },
    { id: 11, particular: 'Other', check: '' }
  ];

  change_related = [
    { id: 1, particular: 'Master Formula Card/Record ', check: '' },
    { id: 2, particular: 'Equipment ', check: '' },
    { id: 3, particular: 'Packing Secondary ', check: '' },
    { id: 4, particular: 'Batch Numbering System', check: '' },
    { id: 5, particular: 'Analytical Method Validation', check: '' },
    { id: 6, particular: 'SOPs ', check: '' },
    { id: 7, particular: 'Standard Analytical Spec', check: '' },
    { id: 8, particular: 'Batch Packin Card/Record', check: '' },
    { id: 9, particular: 'Vendor', check: '' },
    { id: 10, particular: 'Cleaning Validation', check: '' },
    { id: 11, particular: 'Equipment Qualification', check: '' },
    { id: 12, particular: 'Computer System', check: '' },
    { id: 13, particular: 'Batch Manufacturing Record', check: '' },
    { id: 14, particular: 'Process', check: '' },
    { id: 15, particular: 'Artwork', check: '' },
    { id: 16, particular: 'Raw Material Active', check: '' },
    { id: 17, particular: 'Master Validation plan', check: '' },
    { id: 18, particular: 'Stability', check: '' },
    { id: 19, particular: 'Format', check: '' },
    { id: 20, particular: 'Packing Primary', check: '' },
    { id: 21, particular: 'Utilities Qualification', check: '' },
    { id: 22, particular: 'Process Validation', check: '' },
    { id: 23, particular: 'Specification', check: '' },
    { id: 24, particular: 'Regulatory Filling', check: '' },
    { id: 25, particular: 'Master Packing Card/Record', check: '' },
    { id: 26, particular: 'Utilities', check: '' },
    { id: 27, particular: 'Shelf life', check: '' },
    { id: 28, particular: 'Raw Material Excipients', check: '' },
    { id: 29, particular: 'Site Master File', check: '' },
    { id: 30, particular: 'Area Qualification', check: '' },
    { id: 31, particular: 'Other (Specify)', check: '' },

  ];



  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getDepartments();
  }

  getDepartments() {
    this.service.get('changecontrol.php?type=getDepartments').subscribe((response: any) => {
      this.departments = response;
    });
  }

  saveForm(data) {
    if (!data.valid) {
      alert('All fields are required!');
      return;
    }
    let temp = data.value;

    let test = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status']) {
        test[test.length] = department['department_name'];
      }
    }

    let test1 = [];
    for (let i = 0; i < this.classifications.length; i++) {
      let classification = this.classifications[i];
      if (classification['check']) {
        test1[test1.length] = classification['particular'];
      }
    }

    let test2 = [];
    for (let i = 0; i < this.change_related.length; i++) {
      let change = this.change_related[i];
      if (change['check']) {
        test2[test2.length] = change['particular'];
      }
    }

    let test3 = [];
    for (let i = 0; i < this.market_details.length; i++) {
      let market = this.market_details[i];
      if (market['check']) {
        test3[test3.length] = market['particular'];
      }
    }

    temp['departments'] = test;
    temp['classifications'] = test1;
    temp['change_related'] = test2;
    temp['market_details'] = test3;
    this.service.post('changecontrol.php?type=saveform', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        data.resetForm();
        this.router.navigate(['/qms/changecontrol']);
        alert('Successfully send for Approval');
      } else {
        alert('An error has occurred, please try again');
      }
    });
  }

  updateDept(value, i) {
    this.departments[i].status = value;
  }

  updateClass(value, i) {
    this.classifications[i].check = value;
  }

  updateChange(value, i) {
    this.change_related[i].check = value;
  }

  
  updateMarket(value, i) {
    this.market_details[i].check = value;
  }


  checkImpactQuality(value) {
    if (value === 'Yes') {
      this.isImpactQuality = true;
    } else {
      this.isImpactQuality = false;
    }
  }



}