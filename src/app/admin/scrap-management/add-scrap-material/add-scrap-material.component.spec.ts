import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AddScrapMaterialComponent } from './add-scrap-material.component';

describe('AddScrapMaterialComponent', () => {
  let component: AddScrapMaterialComponent;
  let fixture: ComponentFixture<AddScrapMaterialComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AddScrapMaterialComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AddScrapMaterialComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
