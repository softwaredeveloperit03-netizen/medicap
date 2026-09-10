import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ObsoleteComponent } from './obsolete.component';

describe('ObsoleteComponent', () => {
  let component: ObsoleteComponent;
  let fixture: ComponentFixture<ObsoleteComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ObsoleteComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ObsoleteComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
