import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {
  MOA_TYPE_OPTIONS,
  MOA_TYPE_PARAM_LABEL,
  MoaTypeValue,
  normalizeMoaType,
  pickMoaTypeFromRows,
} from '../software-customisation/moa-type.util';
declare let alertify;

@Component({
  selector: 'app-moa-type',
  templateUrl: './moa-type.component.html',
  styleUrls: ['./moa-type.component.css'],
})
export class MoaTypeComponent implements OnInit {
  readonly typeOptions = MOA_TYPE_OPTIONS;
  selectedType: MoaTypeValue | '' = '';
  isLocked = false;
  loading = false;
  isPasswordModalOpen = false;
  password = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadMoaTypeSetting();
  }

  private currentPlantId(): string {
    return String(localStorage.getItem('plant_id') || '').trim();
  }

  private currentEmpId(): string {
    return String(localStorage.getItem('emp_id') || '').trim();
  }

  loadMoaTypeSetting(): void {
    this.loading = true;
    this.service
      .get('qa/custimize.php?type=getSoftware_restrication_dep&dep_name=Master&module=Form Customisation')
      .subscribe({
        next: (response: any) => {
          const saved = pickMoaTypeFromRows(response, this.currentPlantId());
          if (saved) {
            this.selectedType = saved;
            this.isLocked = true;
          }
          this.loading = false;
        },
        error: () => {
          this.loading = false;
        },
      });
  }

  private saveMoaType(showSuccess = true): void {
    const chosenType = normalizeMoaType(this.selectedType);
    if (!chosenType) {
      alertify.error('Please select MOA type');
      return;
    }
    this.selectedType = chosenType;
    const payload = {
      department: 'Master',
      module: 'Form Customisation',
      param_label: MOA_TYPE_PARAM_LABEL,
      restriction: chosenType,
    };
    this.loading = true;
    this.service.post('qa/custimize.php?type=saveSoftware_restrication', JSON.stringify(payload)).subscribe(
      (response: any) => {
        if (response?.status === 'success') {
          if (showSuccess) {
            alertify.success('MOA type locked successfully');
          }
          this.isLocked = true;
          this.password = '';
          this.isPasswordModalOpen = false;
        } else {
          this.isLocked = false;
          alertify.error('Unable to save MOA type');
        }
        this.loading = false;
      },
      () => {
        this.isLocked = false;
        this.loading = false;
        alertify.error('Unable to save MOA type');
      }
    );
  }

  onTypeChange(value: string): void {
    const chosenType = normalizeMoaType(value);
    this.selectedType = chosenType;
    if (!chosenType || this.isLocked || this.loading) {
      return;
    }
    this.isLocked = true;
    this.saveMoaType(true);
  }

  openUnlockPopup(): void {
    this.password = '';
    this.isPasswordModalOpen = true;
  }

  verifyAndUnlock(): void {
    if (!this.password) {
      alertify.error('Please enter password');
      return;
    }
    const empId = this.currentEmpId();
    if (!empId) {
      alertify.error('User not found');
      return;
    }
    const plantId = this.currentPlantId();
    const password = encodeURIComponent(this.password);
    this.service
      .get(
        'checkLogin.php?type=verifyPassword&emp_id=' +
          empId +
          '&plant_id=' +
          plantId +
          '&password=' +
          password
      )
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          this.isLocked = false;
          this.isPasswordModalOpen = false;
          this.password = '';
          alertify.success('Unlocked. You can change MOA type now.');
        } else {
          alertify.error(response?.message || 'Invalid password');
        }
      });
  }
}
