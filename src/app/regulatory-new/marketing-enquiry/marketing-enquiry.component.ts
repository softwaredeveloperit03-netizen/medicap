import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';



@Component({
  selector: 'app-marketing-enquiry',
  templateUrl: './marketing-enquiry.component.html',
  styleUrls: ['./marketing-enquiry.component.css'],
  providers:[DatePipe]

})
export class MarketingEnquiryComponent implements OnInit {

  data = [];
  company='';
  clients;
  client_code = '';
  from_date = '';
  to_date = '';
  enquirylist =[];
  loading;
  viewOpen=false
  isExisting=false
  isNew=false
  product_type:any
  constructor(private service: DataAccessService , private datePipe:DatePipe) {
     
  }

  ngOnInit() {
     this.getenquirylist();
  }

 

  getenquirylist() {
    let userNo=localStorage.getItem('user_no')    
    this.service.get('marketing/lead.php?type=getLeadsLog').subscribe((response: any) => {
      this.enquirylist = response;
     })
  }


  formopen = false;
  selectedenquiry =[];
  selectedenquiryno;
  actions =[];
  
  enquiryactionbtn(index) {
    this.product_type=''

    this.viewOpen = true;
    this.formopen=true
    this.selectedenquiry = this.enquirylist[index];
    this.selectedenquiryno = this.enquirylist[index].enquiry_no;
    this.actions = this.enquirylist[index].actions;

  }


  enquiryprintbtn(enquiry_no) {
    this.service.open('pdf1/marketing.php?type=printenquiry&no='+enquiry_no);
  }

  viewChallan(url) {
    url = this.service.url + '../../upload/product/' + url;
    window.open(url, '_blank');
  }

  closeView(value)
  {
    this.product_type=value
    this.viewOpen=false
    this.formopen=false
    this.viewOpen=false
  
  }




}
