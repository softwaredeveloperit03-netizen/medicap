import { Component } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  ChecklistPage,
  CompanyContact,
  createDefaultChecklistPages,
  createDefaultContacts,
} from '../gmp-inspection-qc-labs.checklist';

declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe],
})
export class NewComponent {
  formNo = 'FQA-007-A';
  sopRef = 'SOP-QA-007';
  revisionNo = '00';
  effectiveDate = '2025-04-16';
  activePage = 1;

  auditDate = '';
  companyName = '';
  address = '';
  cityProvince = '';
  postalCode = '';
  country = '';
  telephone = '';
  faxNo = '';
  emailAddress = '';
  companyContacts: CompanyContact[] = createDefaultContacts();
  approxEmployees = '';
  approxSqFootage = '';
  regulatoryInspection = '';
  regulatoryInspectionDetails = '';
  buildingInteriorAppearance = '';
  buildingExteriorAppearance = '';
  checklistPages: ChecklistPage[] = createDefaultChecklistPages();

  responseOptions = ['', 'Yes', 'No', 'NA', 'Partial'];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe
  ) {
    this.auditDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
  }

  setPage(page: number): void {
    this.activePage = page;
  }

  save(form: NgForm): void {
    if (form.invalid || !this.companyName.trim()) {
      alertify.error('Please fill required fields (Company Name)');
      return;
    }

    const payload = {
      form_no: this.formNo,
      sop_ref: this.sopRef,
      audit_date: this.auditDate,
      company_name: this.companyName,
      address: this.address,
      city_province: this.cityProvince,
      postal_code: this.postalCode,
      country: this.country,
      telephone: this.telephone,
      fax_no: this.faxNo,
      email_address: this.emailAddress,
      company_contacts: this.companyContacts,
      approx_employees: this.approxEmployees,
      approx_sq_footage: this.approxSqFootage,
      regulatory_inspection: this.regulatoryInspection,
      regulatory_inspection_details: this.regulatoryInspectionDetails,
      building_interior_appearance: this.buildingInteriorAppearance,
      building_exterior_appearance: this.buildingExteriorAppearance,
      checklist_data: this.checklistPages,
    };

    this.service
      .post('qa/gmpInspectionQcLabs.php?type=saveGmpInspectionQcLab', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Audit checklist saved successfully');
          this.router.navigate(['/qa/gmp-inspection-qc-labs/log']);
        } else {
          alertify.error(response?.status || 'Failed to save checklist');
        }
      });
  }
}
