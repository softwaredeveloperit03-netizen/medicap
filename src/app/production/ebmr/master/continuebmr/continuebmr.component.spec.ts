import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ContinuebmrComponent } from './continuebmr.component';

describe('ContinuebmrComponent', () => {
  let component: ContinuebmrComponent;
  let fixture: ComponentFixture<ContinuebmrComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ContinuebmrComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ContinuebmrComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
