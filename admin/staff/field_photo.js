function readURL(inp){
    if(inp.files && inp.files[0]){
       const r=new FileReader();
       r.onload=e=>{
          const img=document.getElementById('previewImg');
          img.src=e.target.result;
          img.classList.remove('d-none');
       };
       r.readAsDataURL(inp.files[0]);
    }
  }
  