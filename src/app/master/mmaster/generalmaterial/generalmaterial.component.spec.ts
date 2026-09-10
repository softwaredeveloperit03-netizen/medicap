import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GeneralmaterialComponent } from './generalmaterial.component';

describe('GeneralmaterialComponent', () => {
  let component: GeneralmaterialComponent;
  let fixture: ComponentFixture<GeneralmaterialComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ GeneralmaterialComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(GeneralmaterialComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
