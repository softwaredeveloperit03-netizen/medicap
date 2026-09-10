import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { getRequiredFieldsMessage } from 'src/app/shared/form-validation.helper';

declare let alertify: { error: (msg: string) => void; success: (msg: string) => void };

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  clients: any[] = [];
  client_code = '';
  plants;
  complaint = '';

  /** Display names for validation message (missing required fields in Alertify) */
  complaintFormFieldNames: Record<string, string> = {
    plantID: 'Plant Name',
    client_code: 'Client Name',
    complaint_nature: 'Nature Of Complaint',
    complaint_date: 'Date Of Complaint',
    product_name: 'Name Of Product',
    batch_no: 'Medicap Lot No',
  };

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getclientlist();
    this.plants = JSON.parse(localStorage.getItem('all_plants'));

  }

  getclientlist() {
    this.service.get('common.php?type=getClients').subscribe({
      next: (response: any) => {
        this.clients = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.clients = [];
      },
    });
  }

  plantID;
  products;

  getproductlist() {
    this.service.get('common.php?type=getProductsss&plantID='+this.plantID).subscribe(response => {
      this.products = response;
    });
  }

  saveComplaint(data: NgForm) {
    if (data?.form?.markAllAsTouched) {
      data.form.markAllAsTouched();
    }
    if (!data.valid) {
      alertify.error(getRequiredFieldsMessage(data, this.complaintFormFieldNames, 'Required field(s): '));
      return;
    }
    this.service.post('marketing/complaint.php?type=saveComplaint&plantID=' + this.plantID, JSON.stringify(data.value)).subscribe((response: { status?: string }) => {
      if (response?.status === 'success') {
        data.resetForm();
        this.client_code = '';
        this.complaint = '';
        alertify.success('Submitted successfully.');
        this.router.navigate(['/marketing/complaint']);
      } else {
        alertify.error('Please try again.');
      }
    });
  }
}
