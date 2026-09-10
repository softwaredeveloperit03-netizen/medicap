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
  subtests;
  isNewTest;
  classification = 'Raw Material';
  dosage_form = 'Powder';
  test = '';
  isRawMaterial = true;
  isPackingMaterial = false;
  isFinishProduct = false;
  isInprocess = false;
  test_type='Chemical';
  entries = [];
  constructor(private service: DataAccessService,private router:Router) {
  }

  ngOnInit() {
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

 
  
  addTest(testData) {
    this.isNewTest = false;
    this.service.post('master/test.php?type=saveSubTest', JSON.stringify(testData))
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.router.navigate(['/master/subtest'])
        alertify.success('Test successfully send for Approval');
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
      if (this.classification === this.tests[i].classification && this.dosage_form === this.tests[i].dosage_form && this.test === this.tests[i].subtest && val.includes(value)) {
        this.entries[index] = this.tests[i];
      }
    }
  }

  selectedCountryAdvanced: any[];
  countries: any[] = [];
  filteredCountries: any[];
  filterCountry(event) {
    //in a real application, make a request to a remote url with the query and return filtered results, for demo we filter at client side
    let filtered: any[] = [
      { "name": "Indetification"},
      {"name": "Description"}
    ];
    let query = event.query;
    for (let i = 0; i < this.countries.length; i++) {
      let country = this.countries[i];
      if (country.name.toLowerCase().indexOf(query.toLowerCase()) == 0) {
        filtered.push(country);
      }
    }

    this.filteredCountries = filtered;
  }
}

