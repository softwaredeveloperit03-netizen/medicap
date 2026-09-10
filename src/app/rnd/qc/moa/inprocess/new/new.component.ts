import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  isView = false;
  selectedSpec;
  specifications;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getMoaSpecification();
  }

  getMoaSpecification() {
    this.service.get('qc/method.php?type=getPendingInprocessMOA').subscribe(response => {
      this.specifications = JSON.parse(JSON.stringify(response));
    });
  }

  onSpecificationSelect(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }
  
  addmethod(id) {
    this.router.navigate(['/moa/methods/new/' + id]);
  }

  saveMOA(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let tests = this.selectedSpec['tests'];
    for (let  i = 0; i < tests.length; i++) {
      let test = tests[i];
      test['methods'] = undefined;
      tests[i] = test;
    }

    let specification = this.selectedSpec;
    specification['tests'] = tests;

    this.service.post('qc/moa/inprocess.php?type=saveMOA', JSON.stringify(specification)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('MOA saved successfully');
        this.router.navigate(['/moa/raw']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

}
