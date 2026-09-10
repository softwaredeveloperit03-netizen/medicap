import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EurothermComponent } from './eurotherm.component';

describe('EurothermComponent', () => {
  let component: EurothermComponent;
  let fixture: ComponentFixture<EurothermComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EurothermComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EurothermComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
