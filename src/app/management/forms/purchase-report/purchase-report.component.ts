import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-purchase-report',
  templateUrl: './purchase-report.component.html',
  styleUrls: ['./purchase-report.component.css'],
  providers: [DatePipe]
})
export class PurchaseReportComponent implements OnInit {
  reportForm;
  orders;
  isViewPO = false;
  data = [];
  fromDate = '';
  toDate= '';
  vendors;
  vendor_no='';
  currentDate = new Date().toISOString().split("T")[0];
  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getAllPurchaseOrders();
    this.getVendors();
  }
  norecord= false;
  clearRecord(){
    this.fromDate = '';
    this.toDate = '';
    this.getAllPurchaseOrders();
    this.showclearbtn = false;
  }
  showclearbtn = false;

  getVendors() {
    this.service.get('purchase/po.php?type=getVendors').subscribe(response=>{
      this.vendors=response;
    });
  }

  getAllPurchaseOrders() {
    this.orders = [];
    this.service.get('purchase.php?type=getAllPurchaseOrders&vendor_no=' +this.vendor_no + '&fromDate' + this.fromDate +'&toDate' +this.toDate).subscribe((response:any) => {
      if(response['status'] != 'norecord'){
        this.orders = response;
      }else{
        this.norecord = true;
        this.orders = [];
      }
    });
  }
  getDatewisePurchaseOrders() {
    this.orders = [];
    if(this.fromDate != '' && this.toDate != ''){
      this.showclearbtn = true
      this.service.get('purchase.php?type=getAllPurchaseOrders&from='+this.fromDate+'&to='+this.toDate).subscribe((response:any) => {
        if(response['status'] != 'norecord'){
          this.orders = response;
        }else{
          this.norecord = true;
          this.orders = [];
        }
      });
    }

  }

  viewPO(index) {
    this.data = this.orders[index];
    this.isViewPO = true;
  }

  downloadReport(po_no) {
    this.service.open('pdf1/purchase.php?type=purchaseReport&pono=' + po_no)
  }

  downloadReportLog(){
    this.service.open('pdf1/purchase.php?type=purchaseAllReport&from='+this.fromDate+'&to='+this.toDate);
  }

}
