import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  documentList =[];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
  }

  addData(data) {
    
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.documentList[this.documentList.length] = temp;
    data.resetForm();
  }

  delData(index) {
    this.documentList.splice(index, 1);
  }
 save(){
   let temp={};
   temp['documents']=this.documentList;
  this.service.post('qa/iq/document.php?type=saveDocumentList',JSON.stringify(temp)).subscribe(response =>{
    if(response['status']=='success') {
      alertify.success("Record Inserted Succesfully");
      this.router.navigate(['/iq/documentation']);
    } else {
      alertify.error("Failed");
    }
  });
 }
}
