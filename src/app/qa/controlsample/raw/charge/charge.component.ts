import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-charge',
  templateUrl: './charge.component.html',
  styleUrls: ['./charge.component.css']
})
export class ChargeComponent implements OnInit {

  materials;
  employees;
  units;
  results;

  constructor(private service: DataAccessService, private router: Router) {

  }

  ngOnInit() {
    this.getMaterials();
    this.getEmployees();
    this.getUnits();

  }

  getRawControlSamples(){
    this.service.get('qa/controlsample.php?type=getRawControlSamples').subscribe(response => {
      this.results = response;
    });
  }
  getMaterials() {
    this.service.get('common.php?type=getRawMaterials').subscribe(response => {
      this.materials = response;
    });
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  saveForm(data) {
    if (!data.valid) {
      alert('All fields are required!');
      return;
    }
    this.service.post('qa/controlsample.php?type=saveRawControlSample', JSON.stringify(data.value)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        alert( 'Saved Successfully');
        this.router.navigate(['/controlsample/raw']);
      } else {
       alert('An error has occurred, please try again');
      }
    });
  }

  getEmployees() {
    this.service.get('employee.php?type=getEmp').subscribe(response => {
      this.employees = response;
    });
  }
}
