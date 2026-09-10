import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OverSecureApprovalsComponent } from './over-secure-approvals.component';

describe('OverSecureApprovalsComponent', () => {
  let component: OverSecureApprovalsComponent;
  let fixture: ComponentFixture<OverSecureApprovalsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OverSecureApprovalsComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OverSecureApprovalsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
