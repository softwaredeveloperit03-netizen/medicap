import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TranapprovalComponent } from './tranapproval.component';

describe('TranapprovalComponent', () => {
  let component: TranapprovalComponent;
  let fixture: ComponentFixture<TranapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TranapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TranapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
