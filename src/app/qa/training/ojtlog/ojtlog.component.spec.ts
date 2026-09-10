import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OjtlogComponent } from './ojtlog.component';

describe('OjtlogComponent', () => {
  let component: OjtlogComponent;
  let fixture: ComponentFixture<OjtlogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OjtlogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OjtlogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
