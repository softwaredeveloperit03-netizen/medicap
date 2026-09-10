import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  department_name = localStorage.getItem('department');
  departments: any;
  products: any;
  initiationDate: string = '';
  changeReqForList: string[] = [];
  changeReqForOther: string = '';
  changeCAPA: string = '';
  justification: string = '';
  proposed: string = '';
  capaDetails: string = '';
  capaDate: string = '';
  maxDate: string = '';
  linkedSpecNo = '';
  linkedMoaDocNo = '';
  draftPreviewOpen = false;
  draftPreview: any = null;
  editHistory: any[] = [];

  typeOfChangeOptions = [
    { value: 'Process', label: 'Process' },
    { value: 'Equipment', label: 'Equipment' },
    { value: 'Product', label: 'Product' },
    { value: 'Document', label: 'Document' },
    { value: 'System', label: 'System' },
    { value: 'Facility', label: 'Facility' },
    { value: 'Method', label: 'Method' },
    { value: 'Specification', label: 'Specification' },
    { value: 'Component', label: 'Component' },
    { value: 'Other', label: 'Other' },
  ];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute
  ) {
    this.department_name = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getDepartments();
    this.getProducts();
    const today = new Date();
    this.maxDate = today.toISOString().split('T')[0];
    this.initiationDate = this.maxDate;
    this.applyPrefillFromQuery();
  }

  private applyPrefillFromQuery(): void {
    this.route.queryParams.subscribe((params) => {
      if (!params || String(params['prefill'] || '') !== '1') {
        return;
      }

      if (!this.titleOfcc) {
        this.titleOfcc = params['titleOfcc'] || '';
      }
      if (!this.justification) {
        this.justification = params['justification'] || '';
      }
      if (!this.proposed) {
        this.proposed = params['proposed'] || '';
      }
      this.linkedSpecNo = params['spec_no'] || this.linkedSpecNo;
      this.linkedMoaDocNo = params['moa_doc_no'] || this.linkedMoaDocNo;

      const changeType = params['changeType'] || 'Specification';
      if (changeType && this.changeReqForList.indexOf(changeType) === -1) {
        this.changeReqForList.push(changeType);
      }

      if (this.linkedSpecNo || this.linkedMoaDocNo) {
        this.loadEditHistory();
      }
    });
  }

  isPastOrToday(dateStr: string): boolean {
    if (!dateStr) return true;
    const d = new Date(dateStr);
    const t = new Date();
    t.setHours(0, 0, 0, 0);
    d.setHours(0, 0, 0, 0);
    return d.getTime() <= t.getTime();
  }

  isChangeTypeSelected(value: string): boolean {
    return this.changeReqForList.indexOf(value) !== -1;
  }

  toggleChangeType(value: string) {
    const idx = this.changeReqForList.indexOf(value);
    if (idx === -1) {
      this.changeReqForList.push(value);
    } else {
      this.changeReqForList.splice(idx, 1);
    }
  }

  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe((response: any) => {
      this.products = response;
    });
  }

  getDepartments() {
    return new Promise((res) => {
      this.service
        .get('hr/employee.php?type=get_department_by_designationMeha')
        .subscribe((response: any) => {
          this.departments = response;
          res(response);
        });
    });
  }

  titleOfcc = '';

  saveForm(data: any) {
    if (this.changeReqForList.length === 0) {
      alertify.error('Please select at least one Type of Change');
      return;
    }
    if (this.changeReqForList.indexOf('Other') !== -1 && !this.changeReqForOther?.trim()) {
      alertify.error('Please specify Change Requested For (Other)');
      return;
    }
    if (!this.justification?.trim()) {
      alertify.error('Reason & Justification is required');
      return;
    }
    if (!this.proposed?.trim()) {
      alertify.error('Details of Proposed Change is required');
      return;
    }
    if (!this.changeCAPA) {
      alertify.error('Please select if change is due to CAPA');
      return;
    }
    if (this.changeCAPA === 'YES' && (!this.capaDetails?.trim() || !this.capaDate)) {
      alertify.error('CAPA details and date are required when change is due to CAPA');
      return;
    }
    if (!this.initiationDate) {
      alertify.error('Initiation Date is required');
      return;
    }
    if (!this.isPastOrToday(this.initiationDate)) {
      alertify.error('Initiation Date cannot be in the future');
      return;
    }

    const temp: any = {
      department_name: this.department_name,
      titleOfcc: this.titleOfcc,
      initiationDate: this.initiationDate,
      changeReqFor: this.changeReqForList,
      changeReqForOther: this.changeReqForOther || '',
      JUSTIFICATION: this.justification,
      PROPOSED: this.proposed,
      changeCAPA: this.changeCAPA,
      capaDetails: this.capaDetails || '',
      capaDate: this.capaDate || '',
    };

    this.service
      .post('changecontrol1.php?type=saveformMeha', JSON.stringify(temp))
      .subscribe(
        (response: any) => {
          if (response['status'] === 'success') {
            const onDone = () => {
              this.changeReqForList = [];
              this.changeReqForOther = '';
              this.justification = '';
              this.proposed = '';
              this.capaDetails = '';
              this.capaDate = '';
              const today = new Date();
              this.initiationDate = today.toISOString().split('T')[0];
              this.router.navigate(['/qa/qms/change-control']);
              alertify.success('Successfully Saved!!!');
            };

            if (this.linkedSpecNo) {
              const histPayload = {
                specification_no: this.linkedSpecNo,
                draft_particular: 'Draft submitted to Change Control',
                ctrl_no: response['ctrl_no'] || '',
                ctrl_status: 'Pending'
              };
              this.service
                .post(
                  'qc/specification/raw.php?type=saveSpecificationCcEditHistory',
                  JSON.stringify(histPayload)
                )
                .subscribe(() => onDone(), () => onDone());
            } else if (this.linkedMoaDocNo) {
              const histPayload = {
                doc_no: this.linkedMoaDocNo,
                draft_particular: 'Draft submitted to Change Control',
                ctrl_no: response['ctrl_no'] || '',
                ctrl_status: 'Pending'
              };
              this.service
                .post(
                  'master/moa_stp_copy.php?type=saveMoaStpCcEditHistory',
                  JSON.stringify(histPayload)
                )
                .subscribe(() => onDone(), () => onDone());
            } else {
              onDone();
            }
          } else {
            alertify.error('An error has occurred, please try again');
          }
        },
        () => alertify.error('Error saving form')
      );
  }

  loadEditHistory(): void {
    if (!this.linkedSpecNo && !this.linkedMoaDocNo) {
      this.editHistory = [];
      return;
    }
    if (this.linkedSpecNo) {
      this.service
        .get(
          'qc/specification/raw.php?type=getSpecificationCcEditHistory&specification_no=' +
            encodeURIComponent(this.linkedSpecNo)
        )
        .subscribe((response: any) => {
          this.editHistory = Array.isArray(response) ? response : [];
        }, () => {
          this.editHistory = [];
        });
    } else if (this.linkedMoaDocNo) {
      this.service
        .get(
          'master/moa_stp_copy.php?type=getMoaStpCcEditHistory&doc_no=' +
            encodeURIComponent(this.linkedMoaDocNo)
        )
        .subscribe((response: any) => {
          this.editHistory = Array.isArray(response) ? response : [];
        }, () => {
          this.editHistory = [];
        });
    }
  }

  openDraftPreview(): void {
    if (!this.linkedSpecNo && !this.linkedMoaDocNo) {
      alertify.error('No linked specification draft');
      return;
    }
    if (this.linkedSpecNo) {
      this.service
        .get(
          'qc/specification/raw.php?type=getSpecificationDraftBySpecNo&specification_no=' +
            encodeURIComponent(this.linkedSpecNo)
        )
        .subscribe(
          (response: any) => {
            if (response && response.id) {
              this.draftPreview = response;
              this.draftPreviewOpen = true;
            } else {
              alertify.error('Draft not found for this specification');
            }
          },
          () => alertify.error('Unable to load draft preview')
        );
    } else if (this.linkedMoaDocNo) {
      this.service
        .get(
          'master/moa_stp_copy.php?type=getMoaStpDraftByDocNo&doc_no=' +
            encodeURIComponent(this.linkedMoaDocNo)
        )
        .subscribe(
          (response: any) => {
            if (response && response.id) {
              this.draftPreview = response;
              this.draftPreviewOpen = true;
            } else {
              alertify.error('Draft not found for this MOA/STP');
            }
          },
          () => alertify.error('Unable to load draft preview')
        );
    }
  }
}
