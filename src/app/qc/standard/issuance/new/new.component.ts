import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;


@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results;
  products;
  standards
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getPersons();
    this.getProducts();
    this.getStandards();
  }

  getStandards(){
    this.service.get('qc/standard/master.php?type=getMasters').subscribe(response => {
      this.standards = response;
    })
  }

  getPersons() {
    this.service.get('employee.php?type=getQCPersons').subscribe(response => {
      this.results = response;
    })
  }
  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    })
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required')
    }
    this.service.post('qc/standard/issuance.php?type=saveIssuance', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Data save successfully');
        data.resetForm();
        this.router.navigate(['/qc/standard/issuance']);
      }else{
        alertify.error('Some error occured');
      }
    })
  }

}
