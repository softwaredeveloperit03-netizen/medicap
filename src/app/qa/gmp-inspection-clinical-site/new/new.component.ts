import { Component } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  ChecklistPage,
  SiteContact,
  createDefaultChecklistPages,
  createDefaultContacts,
} from '../gmp-inspection-clinical-site.checklist';

declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe],
})
export class NewComponent {
  formNo = 'FQA-011-A';
  sopRef = 'SOP-QA-011';
  revisionNo = '00';
  effectiveDate = '2025-04-16';
  activePage = 1;
  totalPages = 7;

  auditDate = '';
  clinicalSiteName = '';
  facilityAddress = '';
  cityProvince = '';
  country = '';
  telephone = '';
  emailAddress = '';
  auditors = '';
  auditObjectives = '';
  proposedAgenda = '';
  siteContacts: SiteContact[] = createDefaultContacts();
  checklistPages: ChecklistPage[] = createDefaultChecklistPages();

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

  setResponse(item: { response: string }, value: string): void {
    item.response = item.response === value ? '' : value;
  }

  getActivePage(): ChecklistPage | undefined {
    return this.checklistPages.find((p) => p.page_no === this.activePage);
  }

  save(form: NgForm): void {
    if (form.invalid || !this.clinicalSiteName.trim()) {
      alertify.error('Please fill required fields (Clinical Site Name)');
      return;
    }

    const payload = {
      form_no: this.formNo,
      sop_ref: this.sopRef,
      audit_date: this.auditDate,
      clinical_site_name: this.clinicalSiteName,
      facility_address: this.facilityAddress,
      city_province: this.cityProvince,
      country: this.country,
      telephone: this.telephone,
      email_address: this.emailAddress,
      auditors: this.auditors,
      audit_objectives: this.auditObjectives,
      proposed_agenda: this.proposedAgenda,
      site_contacts: this.siteContacts,
      checklist_data: this.checklistPages,
    };

    this.service
      .post('qa/gmpInspectionClinicalSite.php?type=saveGmpInspectionClinicalSite', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Clinical site audit checklist saved successfully');
          this.router.navigate(['/qa/gmp-inspection-clinical-site/log']);
        } else {
          alertify.error(response?.status || 'Failed to save checklist');
        }
      });
  }
}
