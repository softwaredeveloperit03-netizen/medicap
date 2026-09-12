import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-equipment-work-order-new-form',
  templateUrl: './new-form.component.html',
  styleUrls: ['../work-order-form.theme.css', './new-form.component.css'],
})
export class NewFormComponent implements OnInit {
  department = '';
  equipments: any[] = [];
  areas: any[] = [];
  selectedEquip: any = null;
  equipIndex: any = '';
  areaIndex: any = '';
  today = new Date().toISOString().substring(0, 10);

  form: any = {
    asset_type: '',
    equipment_id: '',
    equipment_name: '',
    equipment_code: '',
    sub_component_id: '',
    is_breakdown: '',
    is_repair: '',
    priority: '',
    available_for_maintenance: '',
    required_for_use: '',
    problem_description: '',
    ref_ncr_no: '',
    submitted_by: '',
    submitted_date: '',
  };

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.department = localStorage.getItem('department') || '';
    this.form.submitted_by =
      localStorage.getItem('emp_name') ||
      localStorage.getItem('username') ||
      localStorage.getItem('emp_id') ||
      '';
    this.form.submitted_date = this.today;
  }

  onAssetTypeChange(): void {
    this.equipIndex = '';
    this.areaIndex = '';
    this.selectedEquip = null;
    this.form.equipment_id = '';
    this.form.equipment_name = '';
    this.form.equipment_code = '';
    this.form.sub_component_id = '';
    if (this.form.asset_type === 'Equipment') {
      this.loadEquipments();
    } else if (this.form.asset_type === 'Area') {
      this.loadAreas();
    }
  }

  loadEquipments(): void {
    const dept = encodeURIComponent(this.department || localStorage.getItem('department') || '');
    // Use existing live API (same as Breakdown) so dropdown works before new PHP is deployed
    this.service
      .get(
        'engineering/maintenance.php?type=getEquipmentForBreakdownByDepartment&department_name=' +
          dept
      )
      .subscribe({
        next: (response) => {
          const rows = Array.isArray(response) ? response : [];
          this.equipments = rows.filter(
            (e) => e && (e.equipment_name || e.equipment_code || e.tag_no)
          );
          if (!this.equipments.length) {
            // Fallback: softer filter via work-order API (after deploy)
            this.service
              .get(
                'engineering/equipment_work_order.php?type=getEquipmentByDepartment&department_name=' +
                  dept
              )
              .subscribe((res2) => {
                this.equipments = Array.isArray(res2) ? (res2 as any[]) : [];
                if (!this.equipments.length) {
                  alertify.error('No equipment found for department: ' + this.department);
                }
              });
          }
        },
        error: () => {
          this.service
            .get(
              'engineering/equipment_work_order.php?type=getEquipmentByDepartment&department_name=' +
                dept
            )
            .subscribe((res2) => {
              this.equipments = Array.isArray(res2) ? (res2 as any[]) : [];
            });
        },
      });
  }

  loadAreas(): void {
    const dept = encodeURIComponent(this.department || localStorage.getItem('department') || '');
    // Prefer dedicated area API when deployed; also build from equipment locations
    this.areas = [];
    this.service
      .get(
        'engineering/equipment_work_order.php?type=getAreasByDepartment&department_name=' + dept
      )
      .subscribe({
        next: (response) => {
          const fromApi = Array.isArray(response) ? (response as any[]) : [];
          if (fromApi.length) {
            this.areas = fromApi.map((a: any) => ({
              ...a,
              sub_component_id: a.sub_component_id || a.id || a.area_name,
            }));
            return;
          }
          this.loadAreasFromEquipment(dept);
        },
        error: () => this.loadAreasFromEquipment(dept),
      });
  }

  private loadAreasFromEquipment(dept: string): void {
    this.service
      .get(
        'engineering/maintenance.php?type=getEquipmentForBreakdownByDepartment&department_name=' +
          dept
      )
      .subscribe((response) => {
        const rows = Array.isArray(response) ? response : [];
        const seen: Record<string, boolean> = {};
        const list: any[] = [];
        rows.forEach((e) => {
          const name = (e?.location || e?.area || e?.section_name || '').toString().trim();
          if (name && !seen[name]) {
            seen[name] = true;
            list.push({
              area_name: name,
              id: name,
              // Prefer a stable id from first equipment sitting in this area
              sub_component_id:
                e?.tag_no || e?.equipment_code || e?.serial_no || name,
            });
          }
        });
        this.areas = list;
        if (!this.areas.length) {
          alertify.error(
            'No area found for department: ' +
              this.department +
              '. You can type the area name below.'
          );
        }
      });
  }

  /** Map equipment master fields → Sub-Component ID# (paper form FEN-001-01-B). */
  private resolveSubComponentFromEquipment(eq: any): string {
    if (!eq) {
      return '';
    }
    return (
      (eq.serial_no || '').toString().trim() ||
      (eq.tag_no || '').toString().trim() ||
      (eq.equipment_code || '').toString().trim() ||
      (eq.model || '').toString().trim() ||
      ''
    );
  }

  onEquipmentChange(index: any): void {
    if (index === '' || index === null || index === undefined) {
      this.selectedEquip = null;
      this.form.equipment_id = '';
      this.form.equipment_name = '';
      this.form.equipment_code = '';
      this.form.sub_component_id = '';
      return;
    }
    const i = Number(index);
    if (isNaN(i) || !this.equipments[i]) {
      return;
    }
    this.selectedEquip = this.equipments[i];
    this.form.equipment_id = this.selectedEquip.id;
    this.form.equipment_name = this.selectedEquip.equipment_name;
    this.form.equipment_code =
      this.selectedEquip.equipment_code || this.selectedEquip.tag_no || '';
    this.form.sub_component_id = this.resolveSubComponentFromEquipment(this.selectedEquip);
  }

  onAreaChange(index: any): void {
    if (index === '' || index === null || index === undefined) {
      this.form.equipment_id = '';
      this.form.equipment_name = '';
      this.form.equipment_code = '';
      this.form.sub_component_id = '';
      return;
    }
    const i = Number(index);
    if (isNaN(i) || !this.areas[i]) {
      return;
    }
    const area = this.areas[i];
    this.form.equipment_id = '';
    this.form.equipment_name = area.area_name;
    this.form.equipment_code = area.area_name;
    this.form.sub_component_id =
      (area.sub_component_id || area.id || area.area_name || '').toString().trim();
  }

  onManualAreaName(name: string): void {
    const v = (name || '').toString().trim();
    this.form.equipment_code = v;
    this.form.sub_component_id = v;
  }

  save(formRef: any): void {
    if (!formRef.valid) {
      alertify.error('Please fill all required fields');
      return;
    }
    if (!this.form.asset_type) {
      alertify.error('Select Equipment or Area');
      return;
    }
    if (!this.form.equipment_name) {
      alertify.error('Please select ' + this.form.asset_type + ' name');
      return;
    }
    if (this.form.asset_type === 'Area' && !this.form.equipment_code) {
      this.form.equipment_code = this.form.equipment_name;
    }
    const normalizeDt = (v: string) => {
      if (!v) {
        return '';
      }
      const s = String(v).replace('T', ' ');
      return /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(s) ? s + ':00' : s;
    };
    const payload = {
      ...this.form,
      available_for_maintenance: normalizeDt(this.form.available_for_maintenance),
      required_for_use: normalizeDt(this.form.required_for_use),
      initiating_department: this.department,
    };
    this.service
      .post('engineering/equipment_work_order.php?type=savePartA', JSON.stringify(payload))
      .subscribe({
        next: (response: any) => {
          if (response?.status === 'success') {
            alertify.success(
              'Work Order ' + (response.work_order_no || '') + ' submitted to Engineering'
            );
            formRef.resetForm();
            this.form.asset_type = '';
            this.form.submitted_by =
              localStorage.getItem('emp_name') ||
              localStorage.getItem('username') ||
              localStorage.getItem('emp_id') ||
              '';
            this.form.submitted_date = this.today;
            this.equipIndex = '';
            this.areaIndex = '';
          } else {
            const detail = response?.error || response?.status || 'error';
            alertify.error('Failed: ' + detail);
          }
        },
        error: (err) => {
          const body = err?.error;
          const detail =
            (body && (body.error || body.status || body.message)) ||
            err?.message ||
            'Server error';
          alertify.error('Submit failed: ' + detail);
        },
      });
  }
}
