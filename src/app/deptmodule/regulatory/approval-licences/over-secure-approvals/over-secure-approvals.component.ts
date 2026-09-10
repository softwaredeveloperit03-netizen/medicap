import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-over-secure-approvals',
  templateUrl: './over-secure-approvals.component.html',
  styleUrls: ['./over-secure-approvals.component.css']
})
export class OverSecureApprovalsComponent implements OnInit {

  isNew = false;
  isView = false;
  results: any
  finalResults: any
  other_licence_type: any;
  licence_type: any
  selectedFile: any
  new() {
    this.isNew = true
  }

  constructor(private service: DataAccessService, private router: Router) {
  }

  handleClick() {
    this.isNew = false;
    this.isView = false;
  }

  ngOnInit(): void {
    this.getDomesticData()
    this.getProduct()
  }

  onLicenceTypeChange(value: string) {
    if (value !== 'other') {
      this.other_licence_type = ''; // Reset other_licence_type if not 'other'
    }
  }

  Submit(data) {
    let temp = data.value
    this.service.post('Dossier/Dossier.php?type=saveDomesticApproval', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        // this.router.navigate(['/qms/deviation']);
        alert('Record Inserted Successfully');
        this.router.navigate(['/regulatory-new/approvalLicences']);

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

  getDomesticData() {
    this.service.get('Dossier/Dossier.php?type=getDomesticLog').subscribe(response => {
      this.finalResults = response;
    })
  }

  onFileChanged6(event) {
    if (event.target.files.length >= 1) {
      this.selectedFile = event.target.files[0];
      console.log('this.selectedFile', this.selectedFile);
    }
  }

  openphoto(file) {
    if (file !== '') {
      window.open(this.service.url + '../../upload/product/' + file);
    } else {
      alertify.error('File not available');
    }
  }
}
