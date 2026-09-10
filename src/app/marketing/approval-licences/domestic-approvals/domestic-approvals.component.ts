import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-domestic-approvals',
  templateUrl: './domestic-approvals.component.html',
  styleUrls: ['./domestic-approvals.component.css']
})
export class DomesticApprovalsComponent implements OnInit {

 
  isNew = false;
  isView = false;
  results: any
  finalResults: any
  other_licence_type: any;
  licence_type: any
  selectedFile

  plantID;  plants:any;



  new() {
    this.isNew = true
  }


  constructor(private service: DataAccessService, private router: Router) {
  }


 
  ngOnInit(): void {
    this.getDomesticData();
    this.getProduct();
    this.getclient();
    this.plants = JSON.parse(localStorage.getItem('all_plants'));

  }



  onLicenceTypeChange(value: string) {
    if (value !== 'other') {
      this.other_licence_type = ''; // Reset other_licence_type if not 'other'
    }
  }

  Submit(data) {


    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    const uploadData = new FormData();


    let temp = data.value


    for(let key in temp){
      let value=temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
    }

 
    this.service.post('Dossier/Dossier.php?type=savecopplicense&docType=DOMESTIC', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
         alert('Record Inserted Successfully');
       this.isNew = false;
       this.getDomesticData();

      } else {
        alert('Failed: An error occured, please try again!');
      }
    })
  }

  getProduct() {
    this.service.get('qa/artwork.php?type=getProduct').subscribe(response => {
      this.results = response;
    })
  }

  clients;

  getclient() {
    this.service.get('Dossier/regulatory.php?type=getinternationalClient').subscribe(response => {
      this.clients = response;
    })
  }


  getDomesticData() {
    this.service.get('Dossier/Dossier.php?type=getDomesticLog&docType=DOMESTIC&plantID='+this.plantID).subscribe(response => {
      this.finalResults = response;
    })
  }


  onFileChanged6(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile = event.target.files[0];
     }
  }


  uploadQuatation(url){
    url = this.service.url + '../../upload/regulatory/' + url;
    window.open(url, '_blank');
  }




 
}
