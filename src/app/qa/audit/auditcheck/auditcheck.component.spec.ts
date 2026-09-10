import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AuditcheckComponent } from './auditcheck.component';

describe('AuditcheckComponent', () => {
  let component: AuditcheckComponent;
  let fixture: ComponentFixture<AuditcheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AuditcheckComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AuditcheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
