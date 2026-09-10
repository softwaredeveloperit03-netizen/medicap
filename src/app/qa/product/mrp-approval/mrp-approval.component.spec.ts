import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MrpApprovalComponent } from './mrp-approval.component';

describe('MrpApprovalComponent', () => {
  let component: MrpApprovalComponent;
  let fixture: ComponentFixture<MrpApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MrpApprovalComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(MrpApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
