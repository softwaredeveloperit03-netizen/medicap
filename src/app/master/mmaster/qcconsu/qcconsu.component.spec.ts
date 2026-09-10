import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QcconsuComponent } from './qcconsu.component';

describe('QcconsuComponent', () => {
  let component: QcconsuComponent;
  let fixture: ComponentFixture<QcconsuComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QcconsuComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QcconsuComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
