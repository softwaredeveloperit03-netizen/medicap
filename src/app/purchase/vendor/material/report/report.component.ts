import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css']
})
export class ReportComponent implements OnInit {



  vendors;
  notice;
  ispayment = false;
  isView = false;
  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit(): void {

    this.getVendors();
    this.getNotice();
  }



  getNotice(){
    this.service.get('purchase/vendor.php?type=getnoticeLog').subscribe(response=>{
      this.notice = response;
    });

  }


  getVendors(){
    this.service.get('purchase/vendor.php?type=getVendorLog').subscribe(response=>{
      this.vendors = response;
    });
  }
  selectedNotice =[];

  view(index){

    this.isView= true;

    this.selectedNotice =  this.notice[index];


  }

  
viewfile1(url) {
  url = this.service.url + '../../upload/vendor/notice/' + url;
window.open(url, '_blank');
}



  
  notice_doc:File;
   
  onFileChanged(event) {
    this.notice_doc = event.target.files[0];
  }

  vendor_no ;



  
  add_notice(data){

    const uploadData = new FormData();

       let temp = data.value;

      for (let key in temp) {
        let value = temp[key];
        uploadData.append(key, value);
      }

  
   
    if (this.notice_doc !== undefined) {
      uploadData.append('notice_doc', this.notice_doc, this.notice_doc.name);
    }
 


    this.service.post('purchase/vendor.php?type=save_notice&vendor_no='+this.vendor_no, uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Notice  Saved successfully');
        this.ispayment= false;
        this.getNotice();
 
        data.reset();
      } else {
        alertify.error(response['status']);
      }
    });


  }

  new(){
    this.ispayment= true;

  }






}
