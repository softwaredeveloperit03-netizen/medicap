import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

 
  selectedFile1: File;
  selectedFile2: File;
  selectedFile3: File;
  selectedFile4: File;

  grades;
  dosages;
  clients;
  gst;
  cess;
  
  styles = [];
  isStyle = false;
  packing_style = '';
  style = '';
  selectedResult=[];
  manufactured_under = '';
  manufactured_for='';
  product_name='';
  grade='';
  min_shelf='';
  shelf_life='';

  
  

  product_type = '';
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getGrades();
    this.getApprovedClients();
    this.getGST();
    this.getCess();
    this.getStyles();
   
  }



  getGST(){
    this.service.get('common.php?type=getGST').subscribe(response=>{
      this.gst=response;
    });
  }

  getCess(){
    this.service.get('common.php?type=getCess').subscribe(response=>{
      this.cess=response;
    });
  }

  getGrades() {
    this.service.get('common.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }

  getDosages(dosage_type) {
    this.service.get('common.php?type=getDosagesByType&dosage_type=' + dosage_type).subscribe(response => {
      this.dosages = response;
    });
  }

  getApprovedClients() {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });
  }

  onFileChanged1(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile1 = event.target.files[0];
    }
  }

  onFileChanged2(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile2 = event.target.files[0];
    }
  }

  onFileChanged3(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile3 = event.target.files[0];
    }
  }

  onFileChanged4(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile4 = event.target.files[0];
    }
  }

  save(data) {
    if (this.manufactured_under=='' || this.min_shelf.length==0 || this.product_name=='' || this.grade=='' || this.shelf_life.length==0 || this.product_type=='' ) {
      alertify.error('Please Enter required Field');
      return;
    }

    let temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile1 !== undefined) {
      uploadData.append('product_lic', this.selectedFile1, this.selectedFile1.name);
    }

    if (this.selectedFile2 !== undefined) {
      uploadData.append('fsc', this.selectedFile2, this.selectedFile2.name);
    }

    if (this.selectedFile3 !== undefined) {
      uploadData.append('copp', this.selectedFile3, this.selectedFile3.name);
    }

    if (this.selectedFile4 !== undefined) {
      uploadData.append('structure', this.selectedFile4, this.selectedFile4.name);
    }

    this.service.post('master/product.php?type=saveProduct', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Product saved successfully');
        data.resetForm();
        this.router.navigate(['/master/product'])
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  checkStyle(value) {
    if (value == 'ADD NEW') {
      this.isStyle = true;
    }
  }

  saveStyle() {
    if (this.style.length !== 0) {
      this.service.get('qa/master.php?type=saveStyle&style=' + this.style).subscribe(response => {
        if (response['status'] == 'success') {
          this.getStyles();
          alertify.success('packing Style Saved Successfully!');
          this.isStyle = false;
          this.style = '';
          this.packing_style = '';
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
    }
  }

  close(value) {
    if (value == 'style') {
      this.isStyle = false;
    }
  }

  getStyles() {
    this.service.get('qa/master.php?type=getStyles').subscribe((response: any) => {
      this.styles = response;
    });
  }

}
