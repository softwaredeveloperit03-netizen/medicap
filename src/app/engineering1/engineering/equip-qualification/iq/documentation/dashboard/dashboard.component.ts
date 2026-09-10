import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  isView = false;
  orders;
  selectedOrder;
  terms_condition=[];
  vendors;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getVendors();
  }

  viewOrder(index) {
    this.selectedOrder = this.orders[index];
    this.terms_condition=JSON.parse(this.selectedOrder['terms_conditions']);
    this.isView = true;
  }
  edit() {
    this.router.navigate(['/documentation/dashboard/edit/'+ this.selectedOrder['id']]);
  }
  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response=>{
      this.vendors=response;
    });
  }
  download(){
    this.service.open('');
   }
}
