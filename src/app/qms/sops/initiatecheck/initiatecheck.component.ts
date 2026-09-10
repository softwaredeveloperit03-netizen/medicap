import { HttpClient } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-initiatecheck',
  templateUrl: './initiatecheck.component.html',
  styleUrls: ['./initiatecheck.component.css'],
})
export class InitiatecheckComponent implements OnInit {
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
  add1_title = 'Additional Field 1';
  add2_title = 'Additional Field 2';
  add3_title = 'Additional Field 3';
  add4_title = 'Additional Field 4';
  add5_title = 'Additional Field 5';

  isView = false;
  isNew = false;
  results;

  sopview;
  selectedResult = [];
  remark = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getsopview();
    this.getpendinginitiation();
    this.get_rights();
  }

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
  }

  getTrueProperties(object: any): { key: string; value: any }[] {
    return Object.entries(object)
      .filter(([key, value]) => value === true)
      .map(([key, value]) => ({ key, value }));
  }
  filteredProperties: string[] = [];
  resultArray1 = [];
  filterProperties() {
    this.resultArray1 = [];
    const resultArray = [];

    const item = this.sopview[0];

    // Check if "purpose" has a true value
    if (item.purpose) {
      const purposeArray = JSON.parse(item.purpose);
      if (purposeArray[0].purposes === true) {
        console.log(purposeArray[0].purposetitle);
        resultArray.push(purposeArray[0].purposetitle);
      }
    }

    // Check if "scope" has a true value
    if (item.scope) {
      const scopeArray = JSON.parse(item.scope);
      if (scopeArray[0].scopes === true) {
        console.log(scopeArray[0].scopetitle);
        resultArray.push(scopeArray[0].scopetitle);
      }
    }
    if (item.role) {
      const roleArray = JSON.parse(item.role);
      if (roleArray[0].roles === true) {
        console.log(roleArray[0].roleArray);
        resultArray.push(roleArray[0].RolesRestitle);
      }
    }
    if (item.definition) {
      const definitionArray = JSON.parse(item.definition);
      if (definitionArray[0].definitions === true) {
        console.log(definitionArray[0].definitiontitle);
        resultArray.push(definitionArray[0].definitiontitle);
      }
    }
    if (item.reference) {
      const externalsArray = JSON.parse(item.reference);
      if (externalsArray[0].externals === true) {
        console.log(externalsArray[0].externaltitle);
        resultArray.push(externalsArray[0].externaltitle);
      }
    }
    if (item.process) {
      const processArray = JSON.parse(item.process);
      if (processArray[0].Processs === true) {
        console.log(processArray[0].Processtitle);
        resultArray.push(processArray[0].Processtitle);
      }
    }
    if (item.procedures) {
      const proceduresArray = JSON.parse(item.procedures);
      if (proceduresArray[0].proceduressss === true) {
        console.log(proceduresArray[0].Proceduretitle);
        resultArray.push(proceduresArray[0].Proceduretitle);
      }
    }
    if (item.abbreviation) {
      const abbreviationArray = JSON.parse(item.abbreviation);
      if (abbreviationArray[0].Abbreviations === true) {
        console.log(abbreviationArray[0].Abbreviationtitle);
        resultArray.push(abbreviationArray[0].Abbreviationtitle);
      }
    }
    if (item.training) {
      const trainingArray = JSON.parse(item.training);
      if (trainingArray[0].trainings === true) {
        console.log(trainingArray[0].Trainingtitle);
        resultArray.push(trainingArray[0].Trainingtitle);
      }
    }
    if (item.distribution) {
      const distributionArray = JSON.parse(item.distribution);
      if (distributionArray[0].Distributions === true) {
        console.log(distributionArray[0].Distributiontitle);
        resultArray.push(distributionArray[0].Distributiontitle);
      }
    }
    if (item.attachments) {
      const attachmentsArray = JSON.parse(item.attachments);
      if (attachmentsArray[0].Attachmentss === true) {
        console.log(attachmentsArray[0].Attachmentstitle);
        resultArray.push(attachmentsArray[0].Attachmentstitle);
      }
    }
    if (item.revision) {
      const revisionArray = JSON.parse(item.revision);
      if (revisionArray[0].revisions === true) {
        console.log(revisionArray[0].Revisiontitle);
        resultArray.push(revisionArray[0].Revisiontitle);
      }
    }
    if (item.adds1) {
      const add1Array = JSON.parse(item.adds1);
      if (add1Array[0].addi1 === true) {
        console.log(add1Array[0].adds1);
        resultArray.push(add1Array[0].add1_title);
      }
    }
    if (item.adds2) {
      const add2Array = JSON.parse(item.adds2);
      if (add2Array[0].addi2 === true) {
        console.log(add2Array[0].adds2);
        resultArray.push(add2Array[0].add2_title);
      }
    }
    if (item.adds3) {
      const add3Array = JSON.parse(item.adds3);
      if (add3Array[0].addi3 === true) {
        console.log(add3Array[0].adds3);
        resultArray.push(add3Array[0].add3_title);
      }
    }
    if (item.adds4) {
      const add4Array = JSON.parse(item.adds4);
      if (add4Array[0].addi4 === true) {
        console.log(add4Array[0].adds4);
        resultArray.push(add4Array[0].add4_title);
      }
    }
    if (item.adds5) {
      const add5Array = JSON.parse(item.adds5);
      if (add5Array[0].addi5 === true) {
        console.log(add5Array[0].adds5);
        resultArray.push(add5Array[0].add5_title);
      }
    }
    this.resultArray1 = resultArray;

    console.log(this.resultArray1);
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
  adds1 = [];
  saveadd1Form(data) {
    let temp = data.value;
    this.adds1[this.adds1.length] = temp;
  }
  adds2 = [];
  saveadds2FormForm(data) {
    let temp = data.value;
    this.adds2[this.adds2.length] = temp;
  }
  adds3 = [];
  saveadd3Form(data) {
    let temp = data.value;
    this.adds3[this.adds3.length] = temp;
  }
  adds4 = [];
  saveadd4Form(data) {
    let temp = data.value;
    this.adds4[this.adds4.length] = temp;
  }
  adds5 = [];
  saveadd5Form(data) {
    let temp = data.value;
    this.adds5[this.adds5.length] = temp;
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
    temp['adds1'] = this.adds1;
    temp['adds2'] = this.adds2;
    temp['adds3'] = this.adds3;
    temp['adds4'] = this.adds4;
    temp['adds5'] = this.adds5;

    this.service
      .post('sops.php?type=savePurposeView1', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success(this.service.t('common.updatedSuccess'));
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
      });
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id')
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
}
