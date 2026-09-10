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
  products;
  clients;
  standards;
  vendors;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getProducts();
    this.getClients();
    this.getStandards();
    this.getVendors();
  }

  getProducts(){
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    })
  }
  getStandards(){
    this.service.get('qc/standard/master.php?type=getMasters').subscribe(response => {
      this.standards = response;
    })
  }
  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    })
  }

  getVendors(){
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    })
  }
  save(data){
    if(!data.valid){
      alertify.error('All fields are required');
      return;
    }
    this.service.post('qc/standard/order.php?type=saveOrdering', JSON.stringify(data.value)).subscribe(response => {
      if(response ['status']== 'success'){
        alertify.success('Data save succeessfully');
        data.resetForm();
          this.router.navigate(['/qc/standard/ordering']);
      }else {
        alertify.error('Failed: An error occured, please try again!');
      }
    })
  }

}
