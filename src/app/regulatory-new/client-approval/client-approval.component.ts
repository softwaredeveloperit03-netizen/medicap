import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
declare let alertify;
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-client-approval',
  templateUrl: './client-approval.component.html',
  styleUrls: ['./client-approval.component.css']
})
export class ClientApprovalComponent implements OnInit {

  isView = false;

  constructor(private service: DataAccessService) {}


  ngOnInit(): void {
    this.getClientFOrLegalApproval();
  }

  isEdit = false;
 
 
  clients;
  getClientFOrLegalApproval() {
    this.service.get('marketing/client.php?type=getClientFOrLegalApproval').subscribe((response: any) => {
        this.clients = response;
    });
  }





    agreFile: File;
    onFileChange2($event) {
        this.agreFile = $event.target.files[0];
    }

    uploadClientAgreementFromLegal() {
   
      const formData = new FormData();
      formData.append('client_code', this.selectedClient['client_code']);
      if (this.agreFile) {
        formData.append('agreFile', this.agreFile, this.agreFile.name);
      }else{
        alertify.error("Please Add Agreement File...");
        return;
      }

      this.service.post('marketing/client.php?type=uploadClientAgreementFromLegal', formData)
        .subscribe((response) => {
          const result = JSON.parse(JSON.stringify(response));
          if (result.status === 'success') {
            alertify.success('Legal Agreement Saved Successfully');
            this.getClientFOrLegalApproval();
            this.isView = false;
          } else {
            alertify.error('An error has occurred: ' + result.status);
          }
        });

    }

 

  selectedClient = [];
  view(item) {
    this.selectedClient = [];
    this.selectedClient = {...item};
    this.isView = true;
  }


 



}
