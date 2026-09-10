import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SettementComponent } from './settement.component';

describe('SettementComponent', () => {
  let component: SettementComponent;
  let fixture: ComponentFixture<SettementComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ SettementComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SettementComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
