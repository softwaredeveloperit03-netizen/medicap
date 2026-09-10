import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-depreview',
  templateUrl: './depreview.component.html',
  styleUrls: ['./depreview.component.css'],
})
export class DepreviewComponent implements OnInit {
  isView = false;
  results: any;
  ctrl_no = '';

  engg = false;
  admin = false;
  production = false;
  ehs = false;
  qc = false;
  store = false;
  micro = false;
  it = false;
  hr = false;
  regulatory = false;
  qa = false;
  rnd = false;

  selectedResult: any = {};
  department: any;
  classification: string = '';
  showQaApproval: boolean = false;
  savedFormData: any = {};

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getCCForconsentAndReview();
    this.getDepartmentMeha();
    this.resetCheckboxes();
  }

  resetCheckboxes() {
    this.engg = false;
    this.admin = false;
    this.production = false;
    this.ehs = false;
    this.qc = false;
    this.store = false;
    this.micro = false;
    this.it = false;
    this.hr = false;
    this.regulatory = false;
    this.qa = false;
    this.rnd = false;
  }

  getCCForconsentAndReview() {
    this.service
      .get(
        'changecontrol1.php?type=getCCForconsentAndReview&deptName=' +
          localStorage.getItem('department')
      )
      .subscribe((response: any) => {
        this.results = response;
      }, (error: any) => {
        console.error('Error fetching change control data:', error);
      });
  }

  view(index: number) {
    this.selectedResult = this.results[index];
    this.isView = true;
    this.showQaApproval = false;
    this.resetCheckboxes();
    // Load classification if exists
    if (this.selectedResult['classification']) {
      this.classification = this.selectedResult['classification'];
    }
  }

  viewDevDoc(url: string) {
    url = this.service.url + '../../upload/changeControl/' + url;
    window.open(url, '_blank');
  }

  getDepartmentMeha() {
    this.service
      .get('hr/employee.php?type=getDepartmentMeha')
      .subscribe((response: any) => {
        this.department = response;
      }, (error: any) => {
        console.error('Error fetching departments:', error);
      });
  }

  update(data: any) {
    if (!data.valid) {
      alertify.error('Please fill all required fields');
      return;
    }

    const temp = data.value;
    temp['classification'] = this.classification;
    // Store the form data to pass to QA approval component
    this.savedFormData = temp;
    
    // Save the form data first
    this.service
      .post(
        'changecontrol1.php?type=saveQaReview&id=' + this.selectedResult['id'],
        JSON.stringify(temp)
      )
      .subscribe((response: any) => {
        if (response['status']) {
          // Update selectedResult with saved data
          this.selectedResult = { ...this.selectedResult, ...temp };
          // Hide the depreview form and show QA Approval component (list view)
          this.isView = false;
          this.showQaApproval = true;
          alertify.success('Impact Assessment Saved Successfully. Please complete QA Approval.');
        } else {
          alertify.error('Failed: An error occurred, please try again!');
        }
      }, (error: any) => {
        alertify.error('Error updating change control');
      });
  }

  handleQaApprovalClose() {
    this.showQaApproval = false;
    this.isView = false;
    this.getCCForconsentAndReview();
  }

  handleQaApprovalSave(data: any) {
    // QA approval saved successfully
    this.showQaApproval = false;
    this.isView = false;
    this.savedFormData = {};
    this.getCCForconsentAndReview();
    alertify.success('Change Control Process Completed Successfully!');
  }
}
