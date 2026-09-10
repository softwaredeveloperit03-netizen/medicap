import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OadditionalComponent } from './oadditional.component';

describe('OadditionalComponent', () => {
  let component: OadditionalComponent;
  let fixture: ComponentFixture<OadditionalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OadditionalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OadditionalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
