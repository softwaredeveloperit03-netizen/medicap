import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-formaster',
  templateUrl: './formaster.component.html',
  styleUrls: ['./formaster.component.css'],
})
export class FormasterComponent implements OnInit {
  purposetitle = 'Purpose';
  scopetitle = 'Scope';
  RolesRestitle = 'Role and Responsibility';
  definitiontitle = 'Definition';
  externaltitle = 'External References And Associated Documents';
  Processtitle = 'Process Overview';
  Proceduretitle = 'Procedure';
  Abbreviationtitle = 'Abbreviation';
  Trainingtitle = 'Training Requirement';
  Distributiontitle = 'Distribution';
  Attachmentstitle = 'Attachments';
  Revisiontitle = 'Revision History';

  isView = false;
  isNew = false;
  results;

  sopview;
  selectedResult = [];
  remark = '';

  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.getsopview();
    this.getpendinginitiation();
    this.get_rights();
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//

  getsopview() {
    this.service.get('sops.php?type=getsopview').subscribe((response) => {
      this.sopview = response;
    });
  }

  getpendinginitiation() {
    this.service
      .get('sops.php?type=intiiate_checking')
      .subscribe((response) => {
        this.results = response;
      });
  }

  view_format = [];
  view(index) {
    this.selectedResult = this.results[index];
    // this.view_format = JSON.parse(this.selectedResult['view_format']);
    this.view_format = this.sopview;

    this.isView = true;
    this.filterProperties();
    console.log(this.filteredProperties);
  }

  // getTrueProperties(object: any): { key: string, value: any }[] {
  //   return Object.entries(object)
  //     .filter(([key, value]) => value === true)
  //     .map(([key, value]) => ({ key, value }));
  // }
  filteredProperties: string[] = [];
  filterProperties() {
    const item = this.sopview[0];

    // Iterate through the properties of the item
    for (const key of Object.keys(item)) {
      // Check if the value is an array with an inner object
      if (
        Array.isArray(item[key]) &&
        item[key].length > 0 &&
        typeof item[key][0] === 'object'
      ) {
        // Check if any key within the inner object has a true value
        const hasTrueValue = Object.keys(item[key][0]).some(
          (innerKey) => item[key][0][innerKey] === true
        );

        if (hasTrueValue) {
          this.filteredProperties.push(key);
        }
      }
    }
  }

  viewList = [];
  purpose = [];
  scope = [];
  role = [];
  defination = [];
  external1 = [];
  rivision = [];
  distribution = [];
  training = [];
  abbrivation = [];
  procedure = [];
  process = [];
  attachments = [];

  save(data) {
    let temp = data.value;
    this.purpose[this.purpose.length] = temp;
  }

  savescopeForm(data) {
    let temp = data.value;
    this.scope[this.scope.length] = temp;

    console.log(this.viewList);
  }

  saveroleForm(data) {
    let temp = data.value;
    this.role[this.role.length] = temp;
  }

  saveDefinationForm(data) {
    let temp = data.value;
    this.defination[this.defination.length] = temp;
  }

  saveExternal(data) {
    let temp = data.value;
    this.external1[this.external1.length] = temp;
  }

  saveProcesstitleForm(data) {
    let temp = data.value;
    this.process[this.process.length] = temp;
  }

  saveprocedureForm(data) {
    let temp = data.value;
    this.procedure[this.procedure.length] = temp;
  }

  saveAbbreviationForm(data) {
    let temp = data.value;
    this.abbrivation[this.abbrivation.length] = temp;
  }

  saveTrainingtitleForm(data) {
    let temp = data.value;
    this.training[this.training.length] = temp;
  }

  saveDistributionForm(data) {
    let temp = data.value;
    this.distribution[this.distribution.length] = temp;
  }

  saverevisionFormForm(data) {
    let temp = data.value;
    this.rivision[this.rivision.length] = temp;
  }

  saveAttachments(data) {
    let temp = data.value;
    this.attachments[this.attachments.length] = temp;
  }

  saveformat_form(data) {
    let temp = data.value;
    temp['purpose'] = this.purpose;
    temp['scope'] = this.scope;
    temp['role'] = this.role;
    temp['defination'] = this.defination;
    temp['external1'] = this.external1;
    temp['process'] = this.process;
    temp['procedure'] = this.procedure;
    temp['abbrivation'] = this.abbrivation;
    temp['training'] = this.training;
    temp['distribution'] = this.distribution;
    temp['rivision'] = this.rivision;
    temp['attachments'] = this.attachments;

    this.service
      .post('sops.php?type=savePurposeView', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success(this.service.t('common.updatedSuccess'));
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
      });
  }
}
