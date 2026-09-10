import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QatrapprovalComponent } from './qatrapproval.component';

describe('QatrapprovalComponent', () => {
  let component: QatrapprovalComponent;
  let fixture: ComponentFixture<QatrapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QatrapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QatrapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
