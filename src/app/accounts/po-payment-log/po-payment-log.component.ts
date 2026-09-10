import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-po-payment-log',
  templateUrl: './po-payment-log.component.html',
  styleUrls: ['./po-payment-log.component.css'],
    providers:[DatePipe]
})
export class PoPaymentLogComponent implements OnInit {

 
   from_date='';
   to_date='';
   today='';
   
   constructor(private service: DataAccessService,private datePipe:DatePipe) {
     this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
     this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
     this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }
 
   ngOnInit() {
     this.getAllPendingPO();
   }
  
   po_type = 'Raw Material';
   results;
 
   getAllPendingPO() {
     this.service.get('purchase/po/raw.php?type=getAllPOLog&po_type=' + this.po_type).subscribe((response) => {
         this.results = response;
     });
   }
 
 
 
   isView = false;
   isPayment = false;
   selectedPO = [];
   selectedBill = [];
   selectedShip = [];
 
   view(data) {
     this.selectedPO = data;
     this.selectedBill = this.selectedPO['selectedBill'];
     this.selectedShip = this.selectedPO['selectedShip'];
     this.isView = true;
   }

   view1(data) {
     this.selectedPO = data;
     this.isPayment = true;
   }
 
   
   searchQuery;
 
   get filteredMaterials(): any[] {
     if (!this.searchQuery || this.searchQuery.trim() === '') {
       return this.results; // If search query is empty or whitespace, return all materials
     }
 
     const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
 
     return this.results.filter((material) => {
       // Check if any field of the material contains the search query
       return Object.entries(material).some(([key, value]) => {
         if (key === 'entry_date') {
           // Convert the value to a Date object if it's not already
           const dateValue = typeof value === 'string' ? new Date(value) : value;
           // Check if the date value is valid and includes the search query
           return (
             dateValue instanceof Date &&
             dateValue.toISOString().slice(0, 10).includes(query)
           );
         } else {
           // Convert field value to lowercase and check if it includes the search query
           return value && value.toString().toLowerCase().includes(query);
         }
       });
     });
   }
    
   
   
   
   downloadPOReport(){
     this.service.open('purchase/po_print.php?type=downloadPOReport&id='+this.selectedPO['id']);
   }
  
 
   }
 
 
 