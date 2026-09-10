import { DatePipe } from '@angular/common';
import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {
  saveIssuedEntry;
  isView = false;
  isView1 = false;
   results;
  from_date = '';
  to_date = '';
  selectedResult = [];
  selectedGRN = [];
  issued = [];
  product_name = '';
  product_type = '';
   
  isNewIssue = false;
  products;
  constructor(private service: DataAccessService, private datePipe: DatePipe,private cdr: ChangeDetectorRef) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    // this.getMaterialOutDetails();
    this.getProducts();
    this.getDispatchProducts();
  }

  
  getDispatchProducts() {
    this.service.get('dispatch.php?type=getStockProducts').subscribe(response => {
      this.results = response;
      this.cdr.detectChanges();
     
    });
  }
  

  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }
  data;

  view(index) {
    this.selectedResult = this.results[index];
    this.data = this.selectedResult['data'];
    this.isView = true;
  }

  results1;


  view1(index) {

    let batch_no = this.data[index]['batch_no'];
    this.isView1 = true;
    this.isView = false;

    this.service.get('dispatch.php?type=getStockBy_Batch&batch_no='+batch_no).subscribe(response => {
      this.results1 = response;
      this.cdr.detectChanges();
     
    });





  }

  btn1(){
    this.isView1 = false ;
    this.isView = true;
    this.results1 = [];
  }
  
}
