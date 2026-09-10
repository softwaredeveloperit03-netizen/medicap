import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RicivcopyComponent } from './ricivcopy.component';

describe('RicivcopyComponent', () => {
  let component: RicivcopyComponent;
  let fixture: ComponentFixture<RicivcopyComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RicivcopyComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RicivcopyComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
