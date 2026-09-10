import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-provisional',
  templateUrl: './provisional.component.html',
  styleUrls: ['./provisional.component.css'],
  providers:[DatePipe]
})
export class ProvisionalComponent implements OnInit {
  results;
  results1;
  selectedResult;
  isView=false;

  state_name = '';
  vendor_for = '';
  vendor_type = '';
  state = '';
  from_date = '';
  to_date = '';

  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getLogs();
    this.getstats();
  }
  getstats(){
    this.service.get('common.php?type=getStates'+this.state).subscribe(response =>{
      this.results1=response;
    });
  }
  getLogs(){
    this.service.get('vendor.php?type=getProvisionalVendors&state='+this.state + '&vendor_type=' + this.vendor_type + '&vendor_for=' + this.vendor_for).subscribe(response =>{
      this.results=response;
    });

  }
  
  download(){
    this.service.open('vendor.php?type=downloadProvisionalVendors&state='+this.state + '&vendor_type=' + this.vendor_type + '&vendor_for=' + this.vendor_for);
   }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true; 
  }

}
