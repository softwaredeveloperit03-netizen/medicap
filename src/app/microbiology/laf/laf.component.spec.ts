import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LafComponent } from './laf.component';

describe('LafComponent', () => {
  let component: LafComponent;
  let fixture: ComponentFixture<LafComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ LafComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(LafComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
