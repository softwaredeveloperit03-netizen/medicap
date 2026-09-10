import { Component } from '@angular/core';
import { NgForm } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { getRequiredFieldsAlertHtml } from 'src/app/shared/form-validation.helper';
import {
  formatCanadianPhone,
  isValidCanadianPhone,
  stripToCanadianPhoneDigits,
} from 'src/app/shared/validators/canadian-phone.validator';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent {
  isDomastic = false;

  agents;
  refred_by = 'Direct Customer';
  client_type = 'Distributor';
  order_category = 'DOMESTIC';
  gst_type = 'VAT';
  percentage_on = '';
  state_code = '';

  divisions = [];
  readonly canadianProvinces: string[] = [
    'Alberta',
    'British Columbia',
    'Manitoba',
    'New Brunswick',
    'Newfoundland and Labrador',
    'Northwest Territories',
    'Nova Scotia',
    'Nunavut',
    'Ontario',
    'Prince Edward Island',
    'Quebec',
    'Saskatchewan',
    'Yukon',
  ];
  isSubmitting = false;

  agentFormFieldNames: Record<string, string> = {
    agent_name: 'Agent Name',
    contact_person: 'Contact Person Name',
    phone: 'Contact No',
    email: 'Email ID',
    percentage_on: 'Percentage On',
    percentage: 'Percentage(%)',
    gst_type: 'TAX Type',
    gst_no: 'TAX No',
    state_code: 'State Name',
    pan_no: 'Pan No',
    address: 'Address',
  };

  constructor(private service: DataAccessService, private router: Router) { }

  submit(form: NgForm) {
    if (this.isSubmitting) {
      return;
    }
    if (form?.form?.markAllAsTouched) {
      form.form.markAllAsTouched();
    }

    const missing = this.getMissingAgentFields(form);
    if (missing.length > 0 || !form.valid) {
      this.showMissingFieldsPopup(form, missing);
      return;
    }

    this.isSubmitting = true;
    const temp = { ...form.value };
    if (temp.phone) {
      const digits = stripToCanadianPhoneDigits(temp.phone);
      temp.phone = digits.length === 10 ? formatCanadianPhone(digits) : String(temp.phone).trim();
    }
    this.service.post('marketing/agent.php?type=saveAgent', JSON.stringify(temp)).subscribe({
      next: (response) => {
        this.isSubmitting = false;
        if (response['status'] == 'success') {
          alertify.success('Agent Saved Successfully');
          form.resetForm();
          this.gst_type = 'VAT';
          this.percentage_on = '';
          this.state_code = '';
        } else {
          alertify.error('Please try Again');
        }
      },
      error: () => {
        this.isSubmitting = false;
        alertify.error('Please try Again');
      },
    });
  }

  private getMissingAgentFields(form: NgForm): string[] {
    const missing: string[] = [];
    const values = form.value || {};

    Object.keys(this.agentFormFieldNames).forEach((key) => {
      const label = this.agentFormFieldNames[key];
      const control = form.controls[key];
      const raw = values[key];
      const empty =
        raw === null ||
        raw === undefined ||
        (typeof raw === 'number' && isNaN(raw)) ||
        String(raw).trim() === '';

      if (control?.errors?.['canadianPhone']) {
        missing.push(`${label} (enter valid Canadian number, e.g. 416-555-1234)`);
        return;
      }
      if (control?.errors?.['email']) {
        missing.push(`${label} (invalid email format)`);
        return;
      }
      if (control?.errors?.['pattern']) {
        if (key === 'percentage') {
          missing.push(`${label} (enter a valid number)`);
        } else {
          missing.push(`${label} (invalid format)`);
        }
        return;
      }
      if (key === 'phone' && !empty && !isValidCanadianPhone(raw)) {
        missing.push(`${label} (enter valid Canadian number, e.g. 416-555-1234)`);
        return;
      }
      if (empty || control?.errors?.['required']) {
        missing.push(label);
      }
    });

    return [...new Set(missing)];
  }

  private showMissingFieldsPopup(form: NgForm, missing: string[]): void {
    const html =
      missing.length > 0
        ? (
          '<p style="margin-bottom:8px;">Please fill the following field(s):</p>' +
          '<ul style="text-align:left;margin:0;padding-left:22px;line-height:1.7;">' +
          missing.map((name) => `<li><strong>${name}</strong></li>`).join('') +
          '</ul>'
        )
        : getRequiredFieldsAlertHtml(form, this.agentFormFieldNames);

    if (typeof alertify !== 'undefined' && alertify.alert) {
      alertify.alert('Incomplete Form', html);
      return;
    }
    alert('Please fill: ' + (missing.length ? missing.join(', ') : 'all required fields'));
  }

  addDivision(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.divisions[this.divisions.length] = data.value;
    data.resetForm();
  }

  delDivision(index) {
    this.divisions.splice(index, 1);
  }

}
