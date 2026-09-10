import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { STANDARD_TEST_TEMPLATES } from '../shared/standard-tests.constants';
declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  public testType = [
    { value: '', label: 'Select test type' },
    { value: 'Chemical', label: 'Chemical' },
    { value: 'Microbiology', label: 'Microbiology' },
    { value: 'Physcial', label: 'Physical Observation' },
    { value: 'Packing', label: 'Packing' },
    { value: 'Water', label: 'Water' },
    { value: 'Indentification', label: 'Indentification' },
    { value: 'LOD', label: 'LOD (Loss on Drying)' },
  ];

  readonly standardTemplates = STANDARD_TEST_TEMPLATES;
  selectedTemplateIndex = -1;
  subtestList: { subtest: string }[] = [];
  formModel = { test_type: '', test: '', testCode: '' };

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {}

  applyStandardTemplate(index: number): void {
    if (index == null || index < 0) return;
    const tpl = this.standardTemplates[index];
    if (!tpl) return;
    this.formModel = {
      test_type: tpl.test_type,
      test: tpl.test,
      testCode: tpl.testCode,
    };
    this.subtestList = tpl.subtestList.map((s) => ({ subtest: s.subtest }));
    alertify.success(`Loaded standard template: ${tpl.test}`);
  }

  addSubtest(data: { valid: boolean; value: { subtest: string }; resetForm?: () => void }): void {
    if (!data.valid) {
      alertify.error('Subtest name is required');
      return;
    }
    this.subtestList.push({ subtest: data.value.subtest.trim() });
    if (data.resetForm) data.resetForm();
  }

  deletesubtest(index: number): void {
    this.subtestList.splice(index, 1);
  }

  addTest(data: { valid: boolean; value: any }): void {
    if (!data.valid) {
      alertify.error('All required fields must be filled');
      return;
    }
    if (!this.subtestList.length) {
      alertify.error('Add at least one subtest');
      return;
    }

    const temp = {
      ...data.value,
      test_type: this.formModel.test_type,
      test: this.formModel.test,
      testCode: this.formModel.testCode,
      subtestList: this.subtestList,
    };

    this.service.post('master/test.php?type=saveTest', JSON.stringify(temp)).subscribe(
      (response) => {
        if (response['status'] === 'success') {
          alertify.success('Test successfully sent for Approval');
          this.router.navigate(['/master/test']);
          this.subtestList = [];
        } else {
          alertify.error('An error has occurred / check for duplicate entry');
        }
      },
      (error: Response) => {
        alertify.error('An error has occurred, http status:' + error.status);
      }
    );
  }
}
