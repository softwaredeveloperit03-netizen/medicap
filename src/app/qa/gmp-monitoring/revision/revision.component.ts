import { Router } from '@angular/router';
import { Location } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-revision',
  templateUrl: './revision.component.html',
  styleUrls: ['./revision.component.css']
})
export class RevisionComponent implements OnInit {

  revisions = [];
  master_list = [];

  isChange = false;
  isChangeControl = false;
  isRevision = false;

  selectedChecklist;
  selectedRevision;

  reactiveForm: FormGroup;
  changeControlForm: FormGroup;
  constructor(private service: DataAccessService, private router: Router, private fb: FormBuilder) {
    this.reactiveForm = this.fb.group({
      department: { value: '', disabled: true },
      section: { value: '', disabled: true },
      area: { value: '', disabled: true },
      gmp_id: { value: '', disabled: true },
      reason: ['', [Validators.required]],
      change_required: ['', [Validators.required]]
    });

    this.changeControlForm = this.fb.group({
      change_related: ['GMP Monitoring Document Related', [Validators.required]],
      change_title: ['', [Validators.required]],
      existing_procedure: ['', [Validators.required]],
      proposed_change: ['', [Validators.required]],
      reason_for_changes: ['', [Validators.required]],
      product_name: ['', [Validators.required]],
      market_details1: [false, [Validators.requiredTrue]],
      market_details2: [false, [Validators.requiredTrue]]
    });
  }

  ngOnInit(): void {
    this.getGMPRevision();
    this.getGMPMonitoring();
  }

  getGMPRevision() {
    this.service.get('qaDepartment.php?type=getGMPRevision').subscribe(response => {
      this.revisions = JSON.parse(JSON.stringify(response));
    });
  }

  getGMPMonitoring() {
    this.service.get('qaDepartment.php?type=getGMPMonitoring&status=active').subscribe(response => {
      this.master_list = JSON.parse(JSON.stringify(response));
    });
  }

  onNewRevision(item) {
    this.isRevision = true;
    this.selectedChecklist = item;
    this.reactiveForm.patchValue({
      department: this.selectedChecklist.department,
      section: this.selectedChecklist.section,
      area: this.selectedChecklist.area,
      gmp_id: this.selectedChecklist.gmp_id
    });
  }

  onChangeControl(item) {
    this.isChangeControl = true;
    this.selectedRevision = item;
  }

  saveGMPRequest() {
    const formData = new FormData();
    formData.append('department', this.selectedChecklist.department);
    formData.append('section', this.selectedChecklist.section);
    formData.append('area', this.selectedChecklist.area);
    formData.append('gmp_id', this.selectedChecklist.gmp_id);
    formData.append('revision_reason', this.reactiveForm.value.reason);
    formData.append('change_required', this.reactiveForm.value.change_required);

    this.service.post('qaDepartment.php?type=saveGMPRevision', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status == 'success') {
        this.isRevision = false;
        this.getGMPMonitoring();
      }
    });
  }

  onApprove(item) {
    const formData = new FormData();
    formData.append('gmp_id', item.gmp_id);

    this.service.post('qaDepartment.php?type=approveRevision', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status == "success") {
        this.getGMPRevision();
      } else {
        alert("Something Went Wrong...");
      }
    });
  }

  saveChangeControl() {
    const formData = new FormData();
    formData.append('gmp_id', this.selectedRevision.gmp_id);
    formData.append('change_related', this.changeControlForm.value.change_related);
    formData.append('change_title', this.changeControlForm.value.change_title);
    formData.append('existing_procedure', this.changeControlForm.value.existing_procedure);
    formData.append('proposed_change', this.changeControlForm.value.proposed_change);
    formData.append('reason_for_changes', this.changeControlForm.value.reason_for_changes);
    formData.append('product_name', this.changeControlForm.value.product_name);
    formData.append('market_details1', this.changeControlForm.value.market_details1);
    formData.append('market_details2', this.changeControlForm.value.market_details2);

    this.service.post('qaDepartment.php?type=saveGMPChangeControl', formData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status == "success") {
        this.getGMPRevision();
      } else {
        alert("Something Went Wrong...");
      }
    });
  }

  close(): void {
    this.router.navigate(['/gmp-monitoring']);
  }

}
