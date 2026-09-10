import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-order',
  templateUrl: './order.component.html',
  styleUrls: ['./order.component.css']
})
export class OrderComponent implements OnInit {

  constructor(private service:DataAccessService,private router : Router) { }

  ngOnInit(): void {
this.getDetails();
  }
  data;
  getDetails(){
    this.service.get('qa/all2.php?type=getDetails').subscribe((response:any) => {
      this.data = response;
     
    });
  }

  full_name;address;email;mo_no;

  isView=false;
  selectedResult=[];
  view(index){
    this.selectedResult=this.data[index]
    this.isView=true;
    this.full_name=this.selectedResult['full_name'];
    this.address=this.selectedResult['address'];
    this.email=this.selectedResult['email'];
    this.mo_no=this.selectedResult['mo_no'];
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

    this.service.post('qa/all2.php?type=save_order',uploadData).subscribe( response => {    
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
