import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-purchase-report',
  templateUrl: './purchase-report.component.html',
  styleUrls: ['./purchase-report.component.css'],
  providers: [DatePipe]
})
export class PurchaseReportComponent implements OnInit {

  isView = false;
  orders;
  selectedOrder;
  remark = '';
  selectedMaterial=[];
  terms_condition=[];
  from_date='';
  to_date='';
  today='';
  status='';
  vendor_no='';
  vendors;
  constructor(private service:DataAccessService,private datePipe:DatePipe, private router: Router) { 
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPendingPO();
    this.getVendors();
  }

  getPendingPO() {
    this.service.get('purchase/po/raw.php?type=getPOLog&vendor_no='+this.vendor_no+'&status='+this.status+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.orders = response;
    });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response=>{
      this.vendors=response;
    });
  }

  viewOrder(index) {
    this.selectedOrder = this.orders[index];
    this.terms_condition=JSON.parse(this.selectedOrder['terms_conditions']);
    this.isView = true;
  }

  edit() {
    this.router.navigate(['/purchase/order/raw/edit/'+ this.selectedOrder['id']]);
  }
  download(){
    this.service.open('purchase/po/raw.php?type=downloadPOLog&vendor_no='+this.vendor_no+'&status='+this.status+'&from_date='+this.from_date+'&to_date='+this.to_date)
  }
 
  downloadPOReport()
  {
    this.service.open('purchase/po/raw.php?type=downloadPOReport&id='+this.selectedOrder['id'])
  }

}
