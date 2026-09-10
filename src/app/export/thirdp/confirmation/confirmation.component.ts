import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-confirmation',
  templateUrl: './confirmation.component.html',
  styleUrls: ['./confirmation.component.css']
})
export class ConfirmationComponent implements OnInit {

  constructor(private service:DataAccessService,private router : Router) { }

  ngOnInit(): void {
this.getDetails();
  }
  data;
  getDetails(){
    this.service.get('qa/all2.php?type=getConfirmOrderDetails').subscribe((response:any) => {
      this.data = response;
     
    });
  }

  ordernumber;orderdate;c_name;c_contact;c_email;c_phone;p_name;p_id;quantity;unit_price
  pym_method;paym_status;paym_amnt;shiping_add;shipping_method;exp_del_date
  isView=false;
  selectedResult=[];
  view(index){
    this.selectedResult=this.data[index]
    this.isView=true;
    this.ordernumber=this.selectedResult['ordernumber'];
    this.orderdate=this.selectedResult['orderdate'];
    this.c_name=this.selectedResult['c_name'];
    this.c_contact=this.selectedResult['c_contact'];
    this.c_email=this.selectedResult['c_email'];
    this.c_phone=this.selectedResult['c_phone'];
    this.p_name=this.selectedResult['p_name'];
    this.p_id=this.selectedResult['p_id'];
    this.quantity=this.selectedResult['quantity'];
    this.unit_price=this.selectedResult['unit_price'];
    this.pym_method=this.selectedResult['pym_method'];
    this.paym_status=this.selectedResult['paym_status'];
    this.paym_amnt=this.selectedResult['paym_amnt'];
    this.shiping_add=this.selectedResult['shiping_add'];
    this.shipping_method=this.selectedResult['shipping_method'];
    this.exp_del_date=this.selectedResult['exp_del_date'];
  }

  selectedFile2:File;
  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
    if (this.selectedFile2 !== undefined) {
      uploadData.append('attachments', this.selectedFile2, this.selectedFile2.name);
    }

    this.service.post('qa/all2.php?type=save_confirm_order',uploadData).subscribe( response => {    
        if (response['status'] == 'success') {
        alert('Saved Successfully');
        // this.router.navigate(['/qa/capa'])
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}

