import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-indexdoc',
  templateUrl: './indexdoc.component.html',
  styleUrls: ['./indexdoc.component.css']
})
export class IndexdocComponent implements OnInit {

 
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
     this.getProduct();
      
  }

 
  products;
  docData;


  getProduct() {
    this.service.get('store/challan.php?type=getProduct').subscribe(response => {
      this.products = response;
    });
  }


  
  requirement = 'Upload';

  documents:File;

  onFileChanged(event) {
    if (event.target.files.length === 1) {
      this.documents = event.target.files[0];
    }
  }





  saveDocuments(data){

    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }
    const uploadData = new FormData();
    let temp= data.value;
 
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
  
    if (this.documents !== undefined) {
      uploadData.append('document', this.documents, this.documents.name);
    }
 
    this.service.post('store/challan.php?type=saveDocuments', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Document Saved!!!');
         data.reset();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



 


}
